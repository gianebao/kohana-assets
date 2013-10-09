<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Asset management.
 *
 * Examples:
 * # Creates compressed assets
 * Assets --do=migrate
 * 
 * Options:
 *   --do      Function to be done.
 * 
 * Functions:
 *   migrate     Creates compressed assets
 * 
 * @author     Gian Carlo Val Ebao
 * @version    1.0
 */
interface Assets_Task_Interface_Assets
{
    /**
     * You can put initializations here.
     *
     * AWS_Dynamo::factory('Message')->build_table();
     */
    function migrate($params);
}