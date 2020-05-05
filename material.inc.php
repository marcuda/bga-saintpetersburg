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
 * material.inc.php
 *
 * SaintPetersburg game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

if (!defined("CARD_PUB")) {
    // Special card types
    define("CARD_CARPENTER_WORKSHOP", 11);
    define("CARD_GOLD_SMELTER", 12);
    define("CARD_PUB", 22);
    define("CARD_WAREHOUSE", 23);
    define("CARD_OBSERVATORY", 25);
    define("CARD_MARIINSKIJ_THEATER", 33);
    define("CARD_TAX_MAN", 61);

    // Phases/types
    define("PHASE_WORKER", "Worker");
    define("PHASE_BUILDING", "Building");
    define("PHASE_ARISTOCRAT", "Aristocrat");
    define("PHASE_TRADING", "Trading");

    define("WORKER_ALL", 0);
    define("WORKER_WOOD", 1);
    define("WORKER_GOLD", 2);
    define("WORKER_WOOL", 3);
    define("WORKER_FUR", 4);
    define("WORKER_SHIP", 5);

    // Card locations
    define("TOP_ROW", "board_top");
    define("BOTTOM_ROW", "board_bottom");
}

/*
 * Card types
 * array index = index of card sprite art (db type_arg)
 * card_name = printed name of card
 * card_type = type of card (db type)
 * card_trade_type = identifier of trading type
 * card_cost = cost in upper right corner
 * card_value = cost reduction when replaced (same as cost for all but village)
 * card_rubles = money gained during scoring
 * card_points = points gained during scoring
 * card_nbr = number of this card in deck
*/
$this->card_types = array(
    0 => array( 
	"card_name" => "Lumberjack",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_WOOD,
	"card_cost" => 3,
	"card_value" => 3,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    1 => array( 
	"card_name" => "Gold Miner",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_GOLD,
	"card_cost" => 4,
	"card_value" => 4,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    2 => array( 
	"card_name" => "Shepherd",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_WOOL,
	"card_cost" => 5,
	"card_value" => 5,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    3 => array( 
	"card_name" => "Fur Trapper",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_FUR,
	"card_cost" => 6,
	"card_value" => 6,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    4 => array( 
	"card_name" => "Ship Builder",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_SHIP,
	"card_cost" => 7,
	"card_value" => 7,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    5 => array( 
	"card_name" => "Czar and Carpenter",
	"card_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_ALL,
	"card_cost" => 8,
	"card_value" => 8,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    CARD_PUB => array( 
	"card_name" => "Pub",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 1,
	"card_value" => 1,
	"card_rubles" => 0,
	"card_points" => 0,//TODO can buy up to 5 points for 2 rubles each
	"card_nbr" => 2
    ),
    CARD_WAREHOUSE => array( // hand size +1
	"card_name" => "Warehouse",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 2,
	"card_value" => 2,
	"card_rubles" => 0,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    24 => array( 
	"card_name" => "Potjomkin's Village",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 2,
	"card_value" => 6,
	"card_rubles" => 0,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    CARD_OBSERVATORY => array( 
	"card_name" => "Observatory", //TODO special ability to draw one card
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 6,
	"card_value" => 6,
	"card_rubles" => 0,
	"card_points" => 1,//TODO no score if used
	"card_nbr" => 2
    ),
    26 => array( 
	"card_name" => "Market",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 5,
	"card_value" => 5,
	"card_rubles" => 0,
	"card_points" => 1,
	"card_nbr" => 5
    ),
    27 => array( 
	"card_name" => "Customs House",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 8,
	"card_value" => 8,
	"card_rubles" => 0,
	"card_points" => 2,
	"card_nbr" => 5
    ),
    28 => array( 
	"card_name" => "Firehouse",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 11,
	"card_value" => 11,
	"card_rubles" => 0,
	"card_points" => 3,
	"card_nbr" => 3
    ),
    29 => array( 
	"card_name" => "Hospital",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 14,
	"card_value" => 14,
	"card_rubles" => 0,
	"card_points" => 4,
	"card_nbr" => 3
    ),
    30 => array( 
	"card_name" => "Library",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 17,
	"card_value" => 17,
	"card_rubles" => 0,
	"card_points" => 5,
	"card_nbr" => 3
    ),
    31 => array( 
	"card_name" => "Theater",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 20,
	"card_value" => 20,
	"card_rubles" => 0,
	"card_points" => 6,
	"card_nbr" => 2
    ),
    32 => array( 
	"card_name" => "Academy",
	"card_type" => PHASE_BUILDING,
	"card_trade_type" => 6,
	"card_cost" => 23,
	"card_value" => 23,
	"card_rubles" => 0,
	"card_points" => 7,
	"card_nbr" => 1
    ),
    44 => array( 
	"card_name" => "Author",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 4,
	"card_value" => 4,
	"card_rubles" => 1,
	"card_points" => 0,
	"card_nbr" => 6
    ),
    45 => array( 
	"card_name" => "Administrator",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 7,
	"card_value" => 7,
	"card_rubles" => 2,
	"card_points" => 0,
	"card_nbr" => 5
    ),
    46 => array( 
	"card_name" => "Warehouse Manager",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 10,
	"card_value" => 10,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 5
    ),
    47 => array( 
	"card_name" => "Secretary",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 12,
	"card_value" => 12,
	"card_rubles" => 4,
	"card_points" => 0,
	"card_nbr" => 4
    ),
    48 => array( 
	"card_name" => "Controller",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 14,
	"card_value" => 14,
	"card_rubles" => 4,
	"card_points" => 1,
	"card_nbr" => 3
    ),
    49 => array( 
	"card_name" => "Judge",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 16,
	"card_value" => 16,
	"card_rubles" => 5,
	"card_points" => 2,
	"card_nbr" => 2
    ),
    50 => array( 
	"card_name" => "Mistress of Ceremonies",
	"card_type" => PHASE_ARISTOCRAT,
	"card_trade_type" => 7,
	"card_cost" => 18,
	"card_value" => 18,
	"card_rubles" => 6,
	"card_points" => 3,
	"card_nbr" => 2
    ),
    CARD_CARPENTER_WORKSHOP => array( 
	"card_name" => "Carpenter Workshop",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_WOOD,
	"card_cost" => 4,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    CARD_GOLD_SMELTER => array( 
	"card_name" => "Gold Smelter",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_GOLD,
	"card_cost" => 6,
	"card_rubles" => 3,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    13 => array( 
	"card_name" => "Weaving Mill",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_WOOL,
	"card_cost" => 8,
	"card_rubles" => 6,
	"card_points" => 0,
	"card_nbr" => 2
    ),
    14 => array( 
	"card_name" => "Fur Shop",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_FUR,
	"card_cost" => 10,
	"card_rubles" => 3,
	"card_points" => 2,
	"card_nbr" => 3
    ),
    15 => array( 
	"card_name" => "Wharf",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_WORKER,
	"card_worker_type" => WORKER_SHIP,
	"card_cost" => 12,
	"card_rubles" => 6,
	"card_points" => 1,
	"card_nbr" => 3
    ),
    CARD_MARIINSKIJ_THEATER => array( 
	"card_name" => "Mariinskij-Theater",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 10,
	"card_rubles" => 0, // 1 per aristocrat
	"card_points" => 0,
	"card_nbr" => 1
    ),
    34 => array( 
	"card_name" => "Bank",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 13,
	"card_rubles" => 5,
	"card_points" => 1,
	"card_nbr" => 1
    ),
    35 => array( 
	"card_name" => "Peterhof",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 14,
	"card_rubles" => 4,
	"card_points" => 2,
	"card_nbr" => 1
    ),
    36 => array( 
	"card_name" => "St Isaac's Cathedral",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 15,
	"card_rubles" => 3,
	"card_points" => 3,
	"card_nbr" => 1
    ),
    37 => array( 
	"card_name" => "Harbor",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 16,
	"card_rubles" => 5,
	"card_points" => 2,
	"card_nbr" => 1
    ),
    38 => array( 
	"card_name" => "Church of the Resurrection of Jesus Christ",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 16,
	"card_rubles" => 2,
	"card_points" => 4,
	"card_nbr" => 1
    ),
    39 => array( 
	"card_name" => "Catherine the Great Palace",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 17,
	"card_rubles" => 1,
	"card_points" => 5,
	"card_nbr" => 1
    ),
    40 => array( 
	"card_name" => "Smolny Cathedral",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 17,
	"card_rubles" => 4,
	"card_points" => 3,
	"card_nbr" => 1
    ),
    41 => array( 
	"card_name" => "Hermitage",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 18,
	"card_rubles" => 3,
	"card_points" => 4,
	"card_nbr" => 1
    ),
    42 => array( 
	"card_name" => "Winter Palace",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_BUILDING,
	"card_cost" => 19,
	"card_rubles" => 2,
	"card_points" => 5,
	"card_nbr" => 1
    ),
    55 => array( 
	"card_name" => "Pope",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 6,
	"card_rubles" => 1,
	"card_points" => 1,
	"card_nbr" => 1
    ),
    56 => array( 
	"card_name" => "Weapon Master",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 8,
	"card_rubles" => 4,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    57 => array( 
	"card_name" => "Chamber Maid",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 8,
	"card_rubles" => 0,
	"card_points" => 2,
	"card_nbr" => 1
    ),
    58 => array( 
	"card_name" => "Builder",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 10,
	"card_rubles" => 5,
	"card_points" => 0,
	"card_nbr" => 1
    ),
    59 => array( 
	"card_name" => "Senator",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 12,
	"card_rubles" => 2,
	"card_points" => 2,
	"card_nbr" => 1
    ),
    60 => array( 
	"card_name" => "Patriarch",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 16,
	"card_rubles" => 0,
	"card_points" => 4,
	"card_nbr" => 1
    ),
    CARD_TAX_MAN => array( 
	"card_name" => "Tax Man",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 17,
	"card_rubles" => 0, // 1 per worker
	"card_points" => 0,
	"card_nbr" => 1
    ),
    62 => array( 
	"card_name" => "Admiral",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 18,
	"card_rubles" => 3,
	"card_points" => 3,
	"card_nbr" => 1
    ),
    63 => array( 
	"card_name" => "Minister of Foreign Affairs",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 3,
	"card_rubles" => 2,
	"card_points" => 4,
	"card_nbr" => 1
    ),
    64 => array( 
	"card_name" => "Czar",
	"card_type" => PHASE_TRADING,
	"card_trade_type" => PHASE_ARISTOCRAT,
	"card_cost" => 24,
	"card_rubles" => 0,
	"card_points" => 6,
	"card_nbr" => 1
    ),
);


