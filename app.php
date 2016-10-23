<?php

/**
 * App Name: Config Management
 * App Description: Provides controls to deal with configurations via the command line.
 * App Action: cli-load-grammar -> loadConfigGrammar @ 50
 * App Action: cli-init         -> declareMySelf     @ 20
 * App Action: cli-command      -> processCommand    @ 50
 */

 /**
 * Allows a user to get and set configurations in the database.
 *
 * @package      BFW
 * @subpackage   Apps
 * @category     Config Management
 * @author       Michael Munger <michael@highpoweredhelp.com>
 */ 


class ConfigManagement extends \PHPAnt\Core\AntApp implements \PHPAnt\Core\AppInterface  {

    /**
     * Instantiates an instance of the ConfigManagement class.
     * Example:
     *
     * <code>
     * $appConfigManagement = new ConfigManagement();
     * </code>
     *
     * @return void
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function __construct() {
        $this->appName = 'Config / Settings Management';
        $this->canReload = false;
        $this->path = __DIR__;
    }

    /**
     * Callback for the cli-load-grammar action, which adds commands specific to this app to the CLI grammar.
     * Example:
     *
     * <code>
     * $appConfigManagement->addHook('cli-load-grammar','loadConfigGrammar');
     * </code>
     *
     * @return array An array of CLI grammar that will be merged with the rest of the grammar. 
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function loadConfigGrammar() {

        $grammar['settings'] = [ 'delete'  => NULL
                               , 'get'     => NULL
                               , 'set'     => NULL
                               , 'show'    => [ 'all' => NULL ]
                               ];

        $this->loaded = true;
        $results['grammar'] = $grammar;
        $results['success'] = true;
        return $results;
    }
    
    /**
     * Callback function that prints to the CLI during cli-init to show this app has loaded.
     * Example:
     *
     * <code>
     * $appConfigManagement->addHook('cli-init','declareMySelf');
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function declareMySelf() {
        if($this->verbosity > 4 && $this->loaded ){
            print("Config / Settings management app loaded.\n");
        }
        return array('success' => true);
    }

    /**
     * Dumps the settings in the database to the screen.
     * Example:
     *
     * <code>
     * $this->settingsShow($something);
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @param string $what the class of settings we wish to show. 
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function settingsShow(\PHPAnt\Core\AppEngine $AE,$what) {
        $m = $AE->Configs->pdo;
        switch ($what) {
            case 'all':
                $sql = "SELECT * FROM settings";
                $stmt = $AE->Configs->pdo->prepare($sql);
                $stmt->execute();
                while($row = $stmt->fetchObject('\PHPAnt\Core\Settings',[$AE->Configs->pdo])) {
                    $row->CLIPrintMe();
                }
                break;
            
            default:
                # code...
                break;
        }
        unset($m);
        return ['success' => true];
    }

    /**
     * Sets a key-value pair in the database
     * Example:
     *
     * <code>
     * $this->settingsSet($cmd)
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @param object $cmd an instantiated object of the Command class.
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function settingsSet($args) {
        $cmd = $args['command'];
        $AE  = $args['AE'];

        /* Skip "settings set [key]" */
        $values = array_slice($cmd->tokens,3,count($cmd->tokens)-3);

        /* Here are the array positions */
        $value = implode(" ", $values);
        $key   = $cmd->tokens[2];
        $success = $AE->Configs->setConfig($key,$value);

        if($this->verbosity > 9) {
            debug_print($cmd->tokens);
            echo str_pad('Key will be:', 20);
            echo $key;
            echo PHP_EOL;

            echo str_pad('Value will be', 20);
            echo $value;
            echo PHP_EOL;

            echo str_pad("setConfig() returned:", 20);
            echo ($success)?'true':'false';
            echo PHP_EOL;

        }
        return ['success' => $success ];        
    }

    /**
     * Gets a specific group of settings based on an array (list) of settings to get. This is expecting to have an array of one or more settings for which we will query the database.
     * Example:
     *
     * <code>
     * $this->settingsGet($cmd)
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @param object $cmd An instantiated class of Command
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function settingsGet($args) {
        $cmd = $args['command'];

        $keys = $cmd->tokens;
        /* Remove "settings" the command tokens to get the search array. */
        array_shift($keys);
        /* Remove "get" the command tokens to get the search array. */
        array_shift($keys);

        $result = $args['AE']->Configs->getConfigs($keys);

        foreach($result as $key => $value) {
            echo str_pad($key, 20);
            echo $value;
            echo PHP_EOL;        
        }
        return ['success' => true];
    }

    /**
     * Deletes a setting from the database
     * Example:
     *
     * <code>
     * $this->settingsDelete($cmd);
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @param object $cmd An instantiated class of Command
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function settingsDelete($args) {
        $result = false; //Guarantee a result.
        $key = $args['command']->tokens[2];
        $result = $args['AE']->Configs->delConfig($key);

        echo ($result ? "$key deleted" : "$key could not be deleted");
        echo PHP_EOL;
        
        return ['success' => $result];
    }

    /**
     * Callback function that processes commands which are parsed by this app as it is called from the cli-command hook.
     * Example:
     *
     * <code>
     * $appConfigManagement->addHook('cli-command','processCommand');
     * </code>
     *
     * @return array An associative array declaring the status / success of the operation.
     * @param array $args An associative array with the CLI Command object any any arguments if necessary.
     * @author Michael Munger <michael@highpoweredhelp.com>
     **/

    function processCommand($args) {
        $cmd = $args['command'];
        $AE  = $args['AE'];

        /* Show all settings */
        if($cmd->startswith("settings show")) {
            switch($cmd->getLastToken()) {
                case 'all':
                    return $this->settingsShow($AE,'all');
                    break;
            }
        } elseif ($cmd->startswith("settings set")) {
            
            return $this->settingsSet($args);

        } elseif ($cmd->startswith("settings get")) {

            return $this->settingsGet($args);

        } elseif ($cmd->startswith("settings delete")) {

            return $this->settingsDelete($args);
        }
        return ['success' => true ];
    }
}