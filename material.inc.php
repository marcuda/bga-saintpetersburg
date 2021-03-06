<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
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

// Define constants
if (!defined("CARD_PUB")) {
    // Special card types
    define("CARD_SHIP", 4);
    define("CARD_CARPENTER_WORKSHOP", 6);
    define("CARD_GOLD_SMELTER", 7);
    define("CARD_PUB", 11);
    define("CARD_WAREHOUSE", 12);
    define("CARD_MARKET", 14);
    define("CARD_OBSERVATORY", 15);
    define("CARD_MARIINSKIJ_THEATER", 22);
    define("CARD_TAX_MAN", 45);

    // Phases/types
    define("PHASE_WORKER", "Worker");
    define("PHASE_BUILDING", "Building");
    define("PHASE_ARISTOCRAT", "Aristocrat");
    define("PHASE_TRADING", "Trading");
    define("PHASE_MARKET", "Market");
    
    // Worker types
    define("WORKER_ALL", clienttranslate("Any"));
    define("WORKER_WOOD", clienttranslate("Wood"));
    define("WORKER_GOLD", clienttranslate("Gold"));
    define("WORKER_WOOL", clienttranslate("Wool"));
    define("WORKER_FUR", clienttranslate("Fur"));
    define("WORKER_SHIP", clienttranslate("Shipping"));
    
    // Market goods
    define("MARKET_NONE", "None");
    define("MARKET_SACK", clienttranslate("Grain sack"));
    define("MARKET_CHICKEN", clienttranslate("Chicken"));
    define("MARKET_APPLE", clienttranslate("Apple"));
    define("MARKET_CABBAGE", clienttranslate("Cabbage"));
    define("MARKET_FISH", clienttranslate("Fish"));
    define("MARKET_JOKER", clienttranslate("Joker"));
    
    // Card locations
    define("TOP_ROW", "board_top");
    define("BOTTOM_ROW", "board_bottom");
    define("ROW_HAND", 33);
    define("ROW_OBSERVATORY", CARD_OBSERVATORY);

    // I18N
    // Cannot translate phases above as they are used in client logic
    define("WORKER", clienttranslate("Worker"));
    define("BUILDING", clienttranslate("Building"));
    define("ARISTOCRAT", clienttranslate("Aristocrat"));
    define("TRADING", clienttranslate("Trading"));
    define("MARKET", clienttranslate("Market"));
    
    // Game options
    define("OPT_SHOW_RUBLES", 100);
    define("OPT_SHOW_HANDS", 101);
    define("OPT_VERSION", 102);
    define("OPT_MARKET", 103);
    define("OPT_BANQUET", 104);
    define("OPT_COMPANY", 105);
    define("OPT_ASSISTANTS", 106);
    define("OPT_EVENTS", 107);
    define("OPT_ASSIGNMENTS", 108);
    define("OPT_OBSTACLES", 109);
}

/*
 * Card infos initialized with the first edition
 * array index = index of card sprite art (db type_arg)
 * card_name = printed name of card
 * card_type = type of card (db type)
 * card_trade_type = identifier of trading type (color)
 * card_worker_type = type of worker for trading (green cards upper right symbol)
 * card_cost = cost in upper left corner
 * card_value = cost reduction when displaced (same as cost for all but village)
 * card_rubles = rubles gained during scoring
 * card_points = points gained during scoring
 * card_nbr = number of this card in deck
 * card_text = explanation of any additional card effects (for tooltip)
*/
$this->card_infos = array(
    0 => array( 
        "card_name" => clienttranslate("Lumberjack"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_WOOD,
        "card_cost" => 3,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    1 => array( 
        "card_name" => clienttranslate("Gold Miner"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_GOLD,
        "card_cost" => 4,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    2 => array( 
        "card_name" => clienttranslate("Shepherd"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_WOOL,
        "card_cost" => 5,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    3 => array( 
        "card_name" => clienttranslate("Fur Trapper"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_FUR,
        "card_cost" => 6,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    CARD_SHIP => array( 
        "card_name" => clienttranslate("Ship Builder"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_SHIP,
        "card_cost" => 7,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    5 => array( 
        "card_name" => clienttranslate("Czar and Carpenter"),
        "card_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_ALL,
        "card_cost" => 8,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("Czar Peter can be displaced by any green trading card")
    ),
    CARD_CARPENTER_WORKSHOP => array(
        "card_name" => clienttranslate("Carpenter Workshop"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_WOOD,
        "card_cost" => 4,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("Blue building cards cost you 1 less ruble to buy")
    ),
    CARD_GOLD_SMELTER => array(
        "card_name" => clienttranslate("Gold Smelter"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_GOLD,
        "card_cost" => 6,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("Red aristocrat cards cost you 1 less ruble to buy")
    ),
    8 => array(
        "card_name" => clienttranslate("Weaving Mill"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_WOOL,
        "card_cost" => 8,
        "card_rubles" => 6,
        "card_points" => 0,
        "card_nbr" => 2
    ),
    9 => array(
        "card_name" => clienttranslate("Fur Shop"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_FUR,
        "card_cost" => 10,
        "card_rubles" => 3,
        "card_points" => 2,
        "card_nbr" => 3
    ),
    10 => array(
        "card_name" => clienttranslate("Wharf"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_WORKER,
        "card_worker_type" => WORKER_SHIP,
        "card_cost" => 12,
        "card_rubles" => 6,
        "card_points" => 1,
        "card_nbr" => 3
    ),
    CARD_PUB => array( 
        "card_name" => clienttranslate("Pub"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 1,
        "card_rubles" => 0,
        "card_points" => 0,
        "card_nbr" => 2,
        "card_text" => clienttranslate("After scoring, you may buy up to 5 points for 2 rubles each")
    ),
    CARD_WAREHOUSE => array(
        "card_name" => clienttranslate("Warehouse"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 2,
        "card_rubles" => 0,
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("You can hold up to 4 cards in your hand")
    ),
    13 => array( 
        "card_name" => clienttranslate("Potjomkin's Village"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 2,
        "card_value" => 6,
        "card_rubles" => 0,
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("Costs 2 rubles to buy but worth 6 when displaced by a trading card")
    ),
    CARD_MARKET => array( 
        "card_name" => clienttranslate("Market"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 5,
        "card_rubles" => 0,
        "card_points" => 1,
        "card_nbr" => 5
    ),
    CARD_OBSERVATORY => array( 
        "card_name" => clienttranslate("Observatory"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 6,
        "card_rubles" => 0,
        "card_points" => 1,
        "card_nbr" => 2,
        "card_text" => clienttranslate("During blue actions you may draw the top-most card from any stack (it may not be the last card). You must then either buy the card, add it to your hand, or discard it. The Observatory then cannot be scored or used until the next round.")
    ),
    16 => array( 
        "card_name" => clienttranslate("Customs House"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 8,
        "card_rubles" => 0,
        "card_points" => 2,
        "card_nbr" => 5
    ),
    17 => array( 
        "card_name" => clienttranslate("Firehouse"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 11,
        "card_rubles" => 0,
        "card_points" => 3,
        "card_nbr" => 3
    ),
    18 => array( 
        "card_name" => clienttranslate("Hospital"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 14,
        "card_rubles" => 0,
        "card_points" => 4,
        "card_nbr" => 3
    ),
    19 => array( 
        "card_name" => clienttranslate("Library"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 17,
        "card_rubles" => 0,
        "card_points" => 5,
        "card_nbr" => 3
    ),
    20 => array( 
        "card_name" => clienttranslate("Theater"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 20,
        "card_rubles" => 0,
        "card_points" => 6,
        "card_nbr" => 2
    ),
    21 => array( 
        "card_name" => clienttranslate("Academy"),
        "card_type" => PHASE_BUILDING,
        "card_trade_type" => 6,
        "card_cost" => 23,
        "card_rubles" => 0,
        "card_points" => 7,
        "card_nbr" => 1
    ),
    CARD_MARIINSKIJ_THEATER => array(
        "card_name" => clienttranslate("Mariinskij-Theater"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 10,
        "card_rubles" => 0, // 1 per aristocrat
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("+1 ruble per red aristocrat in your play area")
    ),
    23 => array(
        "card_name" => clienttranslate("Bank"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 13,
        "card_rubles" => 5,
        "card_points" => 1,
        "card_nbr" => 1
    ),
    24 => array(
        "card_name" => clienttranslate("Peterhof"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 14,
        "card_rubles" => 4,
        "card_points" => 2,
        "card_nbr" => 1
    ),
    25 => array(
        "card_name" => clienttranslate("St Isaac's Cathedral"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 15,
        "card_rubles" => 3,
        "card_points" => 3,
        "card_nbr" => 1
    ),
    26 => array(
        "card_name" => clienttranslate("Church of the Resurrection of Jesus Christ"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 16,
        "card_rubles" => 2,
        "card_points" => 4,
        "card_nbr" => 1
    ),
    27 => array(
        "card_name" => clienttranslate("Harbor"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 16,
        "card_rubles" => 5,
        "card_points" => 2,
        "card_nbr" => 1
    ),
    28 => array(
        "card_name" => clienttranslate("Catherine the Great Palace"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 17,
        "card_rubles" => 1,
        "card_points" => 5,
        "card_nbr" => 1
    ),
    29 => array(
        "card_name" => clienttranslate("Smolny Cathedral"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 17,
        "card_rubles" => 4,
        "card_points" => 3,
        "card_nbr" => 1
    ),
    30 => array(
        "card_name" => clienttranslate("Hermitage"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 18,
        "card_rubles" => 3,
        "card_points" => 4,
        "card_nbr" => 1
    ),
    31 => array(
        "card_name" => clienttranslate("Winter Palace"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_BUILDING,
        "card_cost" => 19,
        "card_rubles" => 2,
        "card_points" => 5,
        "card_nbr" => 1
    ),
    32 => array( 
        "card_name" => clienttranslate("Author"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 4,
        "card_rubles" => 1,
        "card_points" => 0,
        "card_nbr" => 6
    ),
    33 => array( 
        "card_name" => clienttranslate("Administrator"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 7,
        "card_rubles" => 2,
        "card_points" => 0,
        "card_nbr" => 5
    ),
    34 => array( 
        "card_name" => clienttranslate("Warehouse Manager"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 10,
        "card_rubles" => 3,
        "card_points" => 0,
        "card_nbr" => 5
    ),
    35 => array( 
        "card_name" => clienttranslate("Secretary"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 12,
        "card_rubles" => 4,
        "card_points" => 0,
        "card_nbr" => 4
    ),
    36 => array( 
        "card_name" => clienttranslate("Controller"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 14,
        "card_rubles" => 4,
        "card_points" => 1,
        "card_nbr" => 3
    ),
    37 => array( 
        "card_name" => clienttranslate("Judge"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 16,
        "card_rubles" => 5,
        "card_points" => 2,
        "card_nbr" => 2
    ),
    38 => array( 
        "card_name" => clienttranslate("Mistress of Ceremonies"),
        "card_type" => PHASE_ARISTOCRAT,
        "card_trade_type" => 7,
        "card_cost" => 18,
        "card_rubles" => 6,
        "card_points" => 3,
        "card_nbr" => 2
    ),
    39 => array( 
        "card_name" => clienttranslate("Pope"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 6,
        "card_rubles" => 1,
        "card_points" => 1,
        "card_nbr" => 1
    ),
    40 => array( 
        "card_name" => clienttranslate("Chamber Maid"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 8,
        "card_rubles" => 0,
        "card_points" => 2,
        "card_nbr" => 1
    ),
    41 => array( 
        "card_name" => clienttranslate("Weapon Master"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 8,
        "card_rubles" => 4,
        "card_points" => 0,
        "card_nbr" => 1
    ),
    42 => array( 
        "card_name" => clienttranslate("Builder"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 10,
        "card_rubles" => 5,
        "card_points" => 0,
        "card_nbr" => 1
    ),
    43 => array( 
        "card_name" => clienttranslate("Senator"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 12,
        "card_rubles" => 2,
        "card_points" => 2,
        "card_nbr" => 1
    ),
    44 => array( 
        "card_name" => clienttranslate("Patriarch"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 16,
        "card_rubles" => 0,
        "card_points" => 4,
        "card_nbr" => 1
    ),
    CARD_TAX_MAN => array( 
        "card_name" => clienttranslate("Tax Man"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 17,
        "card_rubles" => 0, // 1 per worker
        "card_points" => 0,
        "card_nbr" => 1,
        "card_text" => clienttranslate("+1 ruble per green worker in your play area")
    ),
    46 => array( 
        "card_name" => clienttranslate("Admiral"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 18,
        "card_rubles" => 3,
        "card_points" => 3,
        "card_nbr" => 1
    ),
    47 => array( 
        "card_name" => clienttranslate("Minister of Foreign Affairs"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 20,
        "card_rubles" => 2,
        "card_points" => 4,
        "card_nbr" => 1
    ),
    48 => array( 
        "card_name" => clienttranslate("Czar"),
        "card_type" => PHASE_TRADING,
        "card_trade_type" => PHASE_ARISTOCRAT,
        "card_cost" => 24,
        "card_rubles" => 0,
        "card_points" => 6,
        "card_nbr" => 1
    ),
);

/*
 * Card infos delta of the 2nd edition
 * array index = index of card sprite art (db type_arg)
 * card_model = index of card to use as model (all undefined fields are to be taken in the model)
 * card_name = printed name of card
 * card_type = type of card (db type)
 * card_trade_type = identifier of trading type (color)
 * card_worker_type = type of worker for trading (green cards upper right symbol)
 * card_good = market good granted by the card
 * card_cost = cost in upper left corner
 * card_value = cost reduction when displaced (same as cost for all but village)
 * card_rubles = rubles gained during scoring
 * card_points = points gained during scoring
 * card_nbr = number of this card in deck
 * card_text = explanation of any additional card effects (for tooltip)
 */
$this->card_infos2nd_delta = array(
    CARD_SHIP => array(
        "card_good" => MARKET_SACK,
        "card_nbr" => 1
    ),
    5 => array(
        "card_good" => MARKET_JOKER,
    ),
    13 => array(
        "card_name" => clienttranslate("Potemkin village"),
    ),
    CARD_MARKET => array(
        "card_good" => MARKET_SACK,
        "card_nbr" => 1
    ),
    CARD_OBSERVATORY => array(
        "card_cost" => 7,
    ),
    37 => array(
        "card_cost" => 17,
    ),
    38 => array(
        "card_cost" => 20,
    ),
    CARD_MARIINSKIJ_THEATER => array(
        "card_name" => clienttranslate("Mariinsky Theater"),
        "card_cost" => 15,
        "card_text" => clienttranslate("+1 point per red aristocrat in your play area")
    ),
    23 => array(
        "card_good" => MARKET_SACK,
    ),
    24 => array(
        "card_good" => MARKET_CHICKEN,
    ),
    25 => array(
        "card_good" => MARKET_APPLE,
    ),
    26 => array(
        "card_good" => MARKET_CABBAGE,
    ),
    27 => array(
        "card_good" => MARKET_FISH,
    ),
    39 => array(
        "card_name" => clienttranslate("Abbot"),
    ),
    48 => array(
        "card_name" => clienttranslate("Czarina"),
    ),
    49 => array(
        "card_model" => CARD_SHIP,
        "card_good" => MARKET_CHICKEN,
    ),
    50 => array(
        "card_model" => CARD_SHIP,
        "card_good" => MARKET_APPLE,
    ),
    51 => array(
        "card_model" => CARD_SHIP,
        "card_good" => MARKET_CABBAGE,
    ),
    52 => array(
        "card_model" => CARD_SHIP,
        "card_good" => MARKET_FISH,
    ),
    53 => array(
        "card_model" => CARD_SHIP,
        "card_good" => MARKET_JOKER,
    ),
    54 => array(
        "card_model" => CARD_MARKET,
        "card_good" => MARKET_CHICKEN,
    ),
    55 => array(
        "card_model" => CARD_MARKET,
        "card_good" => MARKET_APPLE,
    ),
    56 => array(
        "card_model" => CARD_MARKET,
        "card_good" => MARKET_CABBAGE,
    ),
    57 => array(
        "card_model" => CARD_MARKET,
        "card_good" => MARKET_FISH,
    ),
);

$addMissingFields = function(&$infos) {
    // Add potentially missing fields.
    foreach ($infos as $idx => $card) {
        if (!isset($card['card_model'])) {
            $infos[$idx]['card_model'] = $idx;
        }
        if (!isset($card['card_value'])) {
            $infos[$idx]['card_value'] = $card['card_cost'];
        }
        if (!isset($card['card_good'])) {
            $infos[$idx]['card_good'] = MARKET_NONE;
        }
    }
};

$this->card_infos2nd = $this->card_infos;
// Override first edition data.
foreach ($this->card_infos2nd_delta as $idx => $card) {
    if (isset($this->card_infos2nd[$idx]) || isset($card['card_model'])) {
        if (!isset($this->card_infos2nd[$idx])) {
            $this->card_infos2nd[$idx] = $this->card_infos2nd[$card['card_model']];
        }
        foreach ($card as $field => $value) {
            $this->card_infos2nd[$idx][$field] = $value;
        }
    } else {
        $this->card_infos2nd[$idx] = $card;
    }
}
// Do not add missing field before the copy for second edition, or some fields won't have a correct value.
$addMissingFields($this->card_infos);
$addMissingFields($this->card_infos2nd);

