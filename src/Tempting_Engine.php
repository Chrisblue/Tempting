<?php
/**
 * Tempting: The fast and lightweight PHP template engine
 *
 * Copyright (c) 2015 Christian Rosenbauer
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Tempting
 * @author     Christian Rosenbauer <admin@chrisblue.org>
 * @license    https://opensource.org/licenses/MIT  The MIT License (MIT)
 * @link       https://github.com/Chrisblue/Tempting/ Project on Github
 *
 */

namespace Tempting;

Class Tempting_Engine {

    const VERSION = '1.0.0';

    private $files = array();
    private $parameters = array();
    private $input;
    private $template;

    // Arrays for internal operations
    private $arrays = array();
    private $vars = array();
    private $partials = array();

    // Default configuration
    public $config = array(
    'enable_partials' => true,
    'enable_arrays' => true,
    'extension' => '.tpl',
    'partial_max_level' => 1,
    'hide_empty_vars' => true,
    'allow_whitespaces' => false,
    'file_cache' => false
    );

    /**
    * Tempting Constructor
    *
    * Loads files based on folders.
    *
    * @param string $_folders (default: array())
    * @param mixed  $_config (default: $this->config)
    *
    */

    public function __construct($_folders,$_config = array()) {
        
        if ($_config !== array()) {
            $this->config = $_config;
        }
        
        foreach($_folders as $folder) {
            // Scanning files based on folders
            $files = array_filter(glob($folder.'/*'.$this->config['extension']), 'is_file');
            $checksum = sha1(implode($files));
            
            if (file_exists(__DIR__.'/cache/'.$checksum.'.fcache')) {
                // Using cache if availiable
                $cache =  file_get_contents(__DIR__.'/cache/'.$checksum.'.fcache');
                $serial = unserialize(gzuncompress($cache));
                $this->files = $serial[0];
                if ($this->config['enable_partials']) {$this->partials = $serial[1];}
                
            }else{
                
                foreach($files as $file) {
                    $newkey = basename($file, $this->config['extension']);
                    $this->files[$newkey] = $file;
                    if ($this->config['enable_partials']) {$this->partials['{{>'.$newkey.'}}'] = file_get_contents($file);}
                }
                
                if ($this->config['file_cache']) {
                    // Creating cache if enabled
                    $cache = serialize(array($this->files,$this->partials));
                    file_put_contents(__DIR__.'/cache/'.$checksum.'.fcache',gzcompress($cache));
                }
            }
        }
        unset($_folders,$_config,$files,$newkey,$cache,$serial,$checksum);
    }
    
    /**
    * Tempting Display-function
    *
    * Renders the input based on configuration.
    *
    * @param string $_template (default: array())
    * @param mixed  $_parameters (default: array())
    *
    * @return string Rendered template
    */
    
    public function Display($_template,$_parameters = array()) {
        
        try {
            if (array_key_exists($_template,$this->files)) {

                    if (($this->template === $_template) && ($this->parameters === $_parameters)) {
                        //Identical Request
                        unset($_parameters,$_template);
                        return $this->input;
                    }else{
                        //Request not identical
                        $this->template = $_template;
                        $this->input = $this->partials['{{>'.$this->template.'}}'];
                        $this->Get_parameters($_parameters);
                
                        if ($this->config['enable_partials']) {$this->Interpret_partials();}
                        if ($this->config['enable_arrays']) {$this->Interpret_arrays();}
                        $this->Interpret_vars();
                    
                        unset($_parameters,$_template);
                        $this->input = ($this->config['hide_empty_vars'] ? preg_replace('/{{.+}}/','',$this->input) : $this->input);
                        return $this->input;
                    }
            }else{
                throw new \Exception("Template '$_template' not found.");
            }
        }catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
    
    /**
    * Tempting Variable-Interpretation
    *
    * Replaces template-variables.
    *
    */
    
    private function Interpret_vars() {
        $this->input = strtr($this->input,$this->vars);
    }
    
    /**
    * Tempting Partial-Interpretation
    *
    * Replaces partials with associated files.
    *
    */
    
    private function Interpret_partials() {
        $i = 0;
        while ($i < $this->config['partial_max_level']) {
            $this->input = strtr($this->input,$this->partials);
            $i++;
        }
    }
    
    /**
    * Tempting Array-Interpretation
    *
    * Replaces template-arrays.
    *
    */
    
    private function Interpret_arrays() {
        
        $pattern = '/{{([#^])([^\s]+)}}(.*?){{\/[^\s]+}}/s';
        $group_replacements = array();
        preg_match_all($pattern, $this->input, $hits, PREG_SET_ORDER);
        
        foreach($hits as $group) {
            if (array_key_exists($group[2],$this->arrays)) {
                $current_array = $this->arrays[$group[2]];
                $i = 0;
            
                $replacements = '';
                $array_size = count($current_array);
                if ($group[1] == '#') {
                    // Array Behaviour
                    while ($i < $array_size) {
                        $current_vars = array();
                        $replacement = $group[3];
                    
                        if (!is_array($current_array[$i])) {
                            // Implicit Iterator
                            $replacement = (strpos($replacement,'{{.}}') !== false ? str_replace('{{.}}',$current_array[$i],$replacement) : '');
                        }else{
                            // Explicit Iterator
                            foreach($current_array[$i] as $key=>$value) {
                                $current_vars['{{'.$key.'}}'] = htmlentities($value);
                                $current_vars['{{!'.$key.'}}'] = $value;
                            }
                            $replacement = strtr($replacement,$current_vars);
                        }
                    
                        $replacements .= $replacement;
                        $i++;
                    }
                    $group_replacements[$group[0]] = $replacements;
                    
                }elseif ($group[1] == '^') {
                    // Behaviour for placeholder
                    $group_replacements[$group[0]] = ($current_array === array() ? $group[3] : '' );
                }
            }else{
                // Behaviour for empty arrays
                $group_replacements[$group[0]] = '';
            }
        }
        $this->input = strtr($this->input,$group_replacements);
        unset($group,$group_replacements);
    }
    
    /**
    * Tempting Parameter-Interpretation
    *
    * Fills internal arrays with data based on parameters.
    *
    * @param mixed  $_parameters
    */
    
    private function Get_parameters($_parameters) {
        
        if($_parameters != $this->parameters) {
            
            unset($this->arrays,$this->vars);
            if (is_array($_parameters)) {$this->parameters = $_parameters;}
            if($this->config['allow_whitespaces']) {$this->input = preg_replace('/({{[>#\/]?)\s*(.+?)\s*(}})/','$1$2$3',$this->input);}
            $this->arrays = array();
            
            foreach($this->parameters as $key=>$value) {
                if (is_array($value)) {
                    $this->arrays[$key] = $value;
                }else {
                    $this->vars['{{'.$key.'}}'] = htmlentities($value);
                    $this->vars['{{!'.$key.'}}'] = $value;
                }
            }
        }
    }
}
