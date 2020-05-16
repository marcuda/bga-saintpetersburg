<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * SaintPetersburg game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        /*
        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),
        */

    ),
    
    // Statistics existing for each player
    "player" => array(

        "actions_taken" => array("id"=> 10,
                    "name" => totranslate("Number of actions taken"),
                    "type" => "int" ),
        "points_aristocrats_end" => array("id"=> 11,
                    "name" => totranslate("Points from Aristocrat types"),
                    "type" => "int" ),
        "points_rubles_end" => array("id"=> 12,
                    "name" => totranslate("Points from Rubles"),
                    "type" => "int" ),
        "points_hand_end" => array("id"=> 13,
                    "name" => totranslate("Points from cards in hand"),
                    "type" => "int" ),
        "points_total" => array("id"=> 14,
                    "name" => totranslate("Points from played cards"),
                    "type" => "int" ),
        "points_Worker" => array("id"=> 15,
                    "name" => totranslate("Points from Workers"),
                    "type" => "int" ),
        "points_Building" => array("id"=> 16,
                    "name" => totranslate("Points from Buildings"),
                    "type" => "int" ),
        "points_Aristocrat" => array("id"=> 17,
                    "name" => totranslate("Points from Aristocrats"),
                    "type" => "int" ),
        "pub_points" => array("id"=> 18,
                    "name" => totranslate("Points bought with Pub"),
                    "type" => "int" ),
        "rubles_total" => array("id"=> 19,
                    "name" => totranslate("Rubles from played cards"),
                    "type" => "int" ),
        "rubles_Worker" => array("id"=> 20,
                    "name" => totranslate("Rubles from Workers"),
                    "type" => "int" ),
        "rubles_Building" => array("id"=> 21,
                    "name" => totranslate("Rubles from Buildings"),
                    "type" => "int" ),
        "rubles_Aristocrat" => array("id"=> 22,
                    "name" => totranslate("Rubles from Aristocrats"),
                    "type" => "int" ),
        "rubles_spent" => array("id"=> 23,
                    "name" => totranslate("Rubles spent"),
                    "type" => "int" ),
        "cards_bought" => array("id"=> 24,
                    "name" => totranslate("Cards bought"),
                    "type" => "int" ),
        "cards_traded" => array("id"=> 25,
                    "name" => totranslate("Trading cards bought"),
                    "type" => "int" ),
        "cards_added" => array("id"=> 26,
                    "name" => totranslate("Cards added to hand"),
                    "type" => "int" ),
        "observatory_draws" => array("id"=> 27,
                    "name" => totranslate("Cards drawn with Observatory"),
                    "type" => "int" ),

    )

);
