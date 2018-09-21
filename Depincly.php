<?php

namespace Ifiri;

/**
 * Create and inject dependencies related to Dependencies Map.
 */
class Depincly {
    private $namespace_prefix = '';
    private $map_location = null;

    private static $deps_map = null;

    private static $created_deps = [];
    private static $precreated_deps_map = [];

    private $delayed = [];
    
    public function __construct($namespace_prefix = '', $map_location = null) {
        $this->namespace_prefix = $namespace_prefix;
        
        // Set default map location in same folder with this file
        if(!$map_location) {
            $this->map_location = dirname(__FILE__) . '/maps/dependencies.map.php';
        } else {
            $this->map_location = $map_location;
        }

        if(!self::$deps_map) {
            $this->load_map();
        }

        if(self::$deps_map && !self::$created_deps) {
            $this->resolve_dependencies();
        }
    }




    private function load_map() {
        try {
            $deps_map = require_once($this->map_location);

            if(is_array($deps_map)) {
                self::$deps_map = $deps_map;
            }
        } catch (Exception $e) {

        }
    }




    private function get_resolved_class_name($class_name) {
        $exploded_name = explode('\\', $class_name);

        $resolved_name = [];
        foreach ($exploded_name as $namespace) {
            if(strpos($this->namespace_prefix, '\\' . $namespace . '\\') !== false) {
                continue;
            }

            $resolved_name[] = $namespace;
        }

        if(empty($resolved_name)) {
            $resolved_name[] = $class_name;
        }

        return implode('\\', $resolved_name);
    }

    private function get_resolved_dependency_name($dependency_name) {
        $exploded_name = explode('\\', $dependency_name);
        $resolved_name = implode('', $exploded_name);

        return $resolved_name;
    }

    private function is_required_dependencies_ready(Array $required_deps, Array $current_deps) {
        $result = ($current_deps == array_keys($required_deps));

        return $result;
    }




    private function resolve_deps_for_class($class, $deps, $create_class = true) {
        $class_deps = [];
        $current_deps = $deps;

        $iterating_deps = $deps;

        if($deps != array_values($deps)) {
            $iterating_deps = array_keys($deps);
        }

        foreach ($iterating_deps as $dependency_key => $dependency_value) {
            $delay = false;

            $dependency_index = $dependency_key;
            $dependency_name = $dependency_value;
            
            if(is_array($dependency_value)) {

                if(isset($dependency_value['name'])) {
                    $dependency_name = $dependency_value['name'];
                }

                if(isset($dependency_value['delay'])) {
                    $delay = $dependency_value['delay'];
                }

            }

            if($delay) {
                $this->delayed[$dependency_name] = $class;

                if(isset($current_deps[$dependency_index]) && $current_deps[$dependency_index]['name'] === $dependency_name) {
                    unset($current_deps[$dependency_index]);
                }
            } else {
                if(isset(self::$deps_map[$dependency_name])) {
                    $this->resolve_deps_for_class($dependency_name, self::$deps_map[$dependency_name]);
                } else {
                    if(!isset(self::$created_deps[$dependency_name])) {
                        $dependency_callable = $this->namespace_prefix . $dependency_name;

                        self::$created_deps[$dependency_name] = new $dependency_callable;
                    }
                }

                if(isset(self::$created_deps[$dependency_name])) {
                    $class_deps[$dependency_name] = self::$created_deps[$dependency_name];
                }
            }
        }

        if($this->is_required_dependencies_ready($class_deps, $current_deps) && !is_object($class) && !isset(self::$created_deps[$class])) {

            $class_callable = $this->namespace_prefix . $class;
        
            self::$created_deps[$class] = new $class_callable(...array_values($class_deps));

            if(isset($this->delayed[$class]) && isset(self::$created_deps[$this->delayed[$class]])) {
                $delayed_depender = self::$created_deps[$this->delayed[$class]];

                if($delayed_depender instanceof IDelayedInjection) {
                    $delayed_depender->set_dependency(self::$created_deps[$class]);

                    unset($this->delayed[$class]);
                }
            }

        }
    }

    private function resolve_dependencies() {
        $precreated = [];

        foreach (self::$deps_map as $class => $settings) {
            $dependencies = $settings;

            if(isset($settings['dependencies'])) {
                $dependencies = $settings['dependencies'];
            }

            if(!isset($settings['lazy'])) {
                $this->resolve_deps_for_class($class, $dependencies);
            } elseif(isset($settings['pre-create'])) {
                self::$precreated_deps_map[$class] = [];

                foreach ($dependencies as $dependency => $alias) {
                    $dep_deps = [];
                    
                    if(isset(self::$deps_map[$dependency])) {
                        $dep_deps = self::$deps_map[$dependency];
                    }

                    $this->resolve_deps_for_class($dependency, $dep_deps, false);

                    self::$precreated_deps_map[$class][] = $dependency;
                }
            }
        }
    }




    public function inject_dependencies_to($class, $container = null) {
        $dependencies = $this->get_created_dependencies();

        if(is_null($container)) {
            $container = $class;
        }

        if(is_object($class)) {
            $class_path = get_class($class);
        } else {
            $class_path = $class;
        }

        $class_name = $this->get_resolved_class_name($class_path);

        if(isset(self::$deps_map[$class_name])) {
            $settings_for_class = self::$deps_map[$class_name];

            $deps_for_class = $settings_for_class['dependencies'];
            if(isset($settings_for_class['dependencies'])) {
                $deps_for_class = $settings_for_class['dependencies'];
            }

            if(isset($settings_for_class['lazy']) && $settings_for_class['lazy']) {
                $this->resolve_deps_for_class($class, $deps_for_class);
            }

            foreach ($deps_for_class as $dep_key => $dep_value) {
                $dep_name = $dep_value;

                if(is_string($dep_key)) {
                    $dep_name = $dep_key;
                    $resolved_name = $dep_value;
                } else {
                    $resolved_name = $this->get_resolved_dependency_name($dep_name);
                }

                if(isset(self::$created_deps[$dep_name])) {
                    $dep_instance = self::$created_deps[$dep_name];

                    $container->$resolved_name = $dep_instance;
                }
            }
        }
    }

    public function get_created_dependencies() {
        return self::$created_deps;
    }

    public function get_created_dependency($class_name) {
        $class_name = $this->get_resolved_class_name($class_name);
        
        if(array_key_exists($class_name, self::$created_deps)) {
            return self::$created_deps[$class_name];
        }

        return null;
    }

    public function get_created_dependency_by($class_name) {
        $class_name = $this->get_resolved_class_name($class_name);

        if(array_key_exists($class_name, self::$created_deps)) {
            return self::$created_deps[$class_name];
        } else {
            if(array_key_exists($class_name, self::$precreated_deps_map)) {
                $created_deps = [];

                foreach (self::$precreated_deps_map[$class_name] as $dependency_name) {
                    if(!isset(self::$created_deps[$dependency_name])) {
                        $dependency_callable = $this->namespace_prefix . $dependency_name;

                        self::$created_deps[$dependency_name] = new $dependency_callable;
                    }

                    $created_deps[] = self::$created_deps[$dependency_name];
                }

                return $created_deps;
            }
        }

        return null;
    }
}