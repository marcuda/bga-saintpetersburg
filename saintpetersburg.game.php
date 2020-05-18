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
  * saintpetersburg.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');


class SaintPetersburg extends Table
{
	function __construct()
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
	    parent::__construct();
        
	    self::initGameStateLabels(array(
		"starting_player_" . PHASE_WORKER => 10,     // player_id holding Worker token (stone)
		"starting_player_" . PHASE_BUILDING => 11,   // player_id holding Building token
		"starting_player_" . PHASE_ARISTOCRAT => 12, // player_id holding Aristocrat token
		"starting_player_" . PHASE_TRADING => 13,    // player_id holding Trading token
		"selected_card" => 14,        // card_id of player selected card
		"selected_row" => 15,         // board row (or other location as specified) of player selected card
		"num_pass" => 16,             // number of players that have consecutively passed in this phase
		"current_phase" => 17,        // current phase number, always increasing
		"last_round" => 18,           // 1 if the end state has been triggered in the current round
                "observatory_0_id" => 19,      // card_id of first Observatory card
                "observatory_1_id" => 20,      // card_id of second Observatory card
                "observatory_0_used" => 21,    // 1 if first Observatory has been used this round
                "observatory_1_used" => 22,    // 1 if second Observatory has been used this round
                "activated_observatory" => 23, // index (0/1) of Observatory being actively used
	    ));        

	    $this->cards = self::getNew("module.common.deck");
	    $this->cards->init("card");
            $this->deck_size = array( // full stack sizes for progression
                PHASE_WORKER => 31,
                PHASE_BUILDING => 28,
                PHASE_ARISTOCRAT => 27,
                PHASE_TRADING => 30
            );

	    $this->phases = array(
		PHASE_WORKER,
		PHASE_BUILDING,
		PHASE_ARISTOCRAT,
		PHASE_TRADING
	    );
	}
	
    protected function getGameName()
    {
		// Used for translations and stuff. Please do not modify.
        return "saintpetersburg";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
	// Player money is tie breaker and so held in aux score
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score_aux) VALUES ";
        $values = array();
        foreach($players as $player_id => $player)
        {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."',25)";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
	// Player order for each phase
	$starting_tokens = array();
	foreach ($this->phases as $phase) {
	    $starting_tokens[] = "starting_player_" . $phase;
	}
	shuffle($starting_tokens);

	$player_ids = array_keys($players);
	$num_players = count($players);

	for ($i=0; $i<4; $i++) {
	    $token = $starting_tokens[$i];
	    $player_id = $player_ids[$i % $num_players];
	    self::setGameStateInitialValue($token, $player_id);
	}

	self::setGameStateInitialValue("selected_card", -1);
	self::setGameStateInitialValue("selected_row", -1);
	self::setGameStateInitialValue("num_pass", 0);
	self::setGameStateInitialValue("current_phase", 0);
	self::setGameStateInitialValue("last_round", 0);
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('player', "actions_taken", 0);
        self::initStat('player', "rubles_spent", 0);
        self::initStat('player', "rubles_total", 0);
        self::initStat('player', "rubles_Worker", 0);
        self::initStat('player', "rubles_Building", 0);
        self::initStat('player', "rubles_Aristocrat", 0);
        self::initStat('player', "points_total", 0);
        self::initStat('player', "points_Worker", 0);
        self::initStat('player', "points_Building", 0);
        self::initStat('player', "points_Aristocrat", 0);
        self::initStat('player', "cards_bought", 0);
        self::initStat('player', "cards_added", 0);
        self::initStat('player', "cards_traded", 0);
        self::initStat('player', "pub_points", 0);
        self::initStat('player', "observatory_draws", 0);
        self::initStat('player', "points_aristocrats_end", 0);
        self::initStat('player', "points_rubles_end", 0);
        self::initStat('player', "points_hand_end", 0);

	// Init cards and decks
	// Create all cards
	$cards = array();
	foreach ($this->card_types as $idx => $card) {
	    $cards[] = array(
		'type' => $card['card_type'],
		'type_arg' => $idx,
		'nbr' => $card['card_nbr']
	    );
	}
	$this->cards->createCards($cards);

	// Split into decks for each type
	foreach ($this->phases as $phase) {
	    $cards = $this->cards->getCardsOfType($phase);
	    $this->cards->moveCards(array_column($cards, 'id'), 'deck_' . $phase);
	    $this->cards->shuffle('deck_' . $phase);
	}

        // Initialize globals to handle Observatory use
        $obs = $this->cards->getCardsOfType(PHASE_BUILDING, CARD_OBSERVATORY);
        $i = 0;
        foreach ($obs as $card) {
	    self::setGameStateInitialValue("observatory_" . $i . "_id", $card['id']);
	    self::setGameStateInitialValue("observatory_" . $i . "_used", 0);
            $i++;
        }
	self::setGameStateInitialValue("activated_observatory", -1);

	// Starting draw based on number of players
	if ($num_players == 2) {
	    $first_draw = 4;
	} else if ($num_players == 3) {
	    $first_draw = 6;
	} else { // 4
	    $first_draw = 8;
	}

	for ($i=0; $i<$first_draw; $i++) {
	    $this->cards->pickCardForLocation('deck_' . PHASE_WORKER, TOP_ROW, $i);
	}

        // Activate first player (which is in general a good idea :))
	$this->gamestate->changeActivePlayer(self::getGameStateValue("starting_player_" . PHASE_WORKER));

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);
  
	$players = self::loadPlayersBasicInfos();
	$tables = array();
	$hands = array();
	foreach ($players as $player_id => $player)
	{
	    $tables[$player_id] = $this->cards->getCardsInLocation('table', $player_id);
	    $hands[$player_id] = count($this->cards->getCardsInLocation('hand', $player_id));
	}
	$result['player_tables'] = $tables;
	$result['player_hands'] = $hands;

	$tokens = array();
	foreach ($this->phases as $token_phase) {
	    $token = "starting_player_" . $token_phase;
	    $token_player = self::getGameStateValue($token);
            // Client expects two elements here as same input is used for new round logic.
            // In this case we don't have (or need) that information so set both to current.
            $tokens[$token_phase] = array(
                'current' => $token_player,
                'next' => $token_player
            );
	}
	$result['tokens'] = $tokens;

	$result['phase'] = $this->phases[self::getGameStateValue('current_phase') % 4];

	$result[TOP_ROW] = $this->cards->getCardsInLocation(TOP_ROW);
	$result[BOTTOM_ROW] = $this->cards->getCardsInLocation(BOTTOM_ROW);
	$result['hand'] = $this->cards->getPlayerHand($current_player_id);
	$result['rubles'] = self::dbGetRubles($current_player_id);

	$result['decks'] = $this->cards->countCardsInLocations();
        $result['card_types'] = $this->card_types;

        $obs = array();
        for ($i=0; $i<2; $i++) {
            $obs[] = array(
                'id' => self::getGameStateValue('observatory_' . $i . '_id'),
                'used' => self::getGameStateValue('observatory_' . $i . '_used'),
            );
        }
        $result['observatory'] = $obs;
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // Game ends after round where any stack is emptied
        // Set progress to inverse of percent left in smallest stack,
        // plus count each phase in the round as 3%
        $val = 0;

        // Find percentage of smallest stack
        $counts = $this->cards->countCardsInLocations();
        foreach ($this->phases as $phase) {
            if (key_exists('deck_' . $phase, $counts)) {
                $percent = $counts['deck_' . $phase] / $this->deck_size[$phase];
                $val = max($val, 100 * (1 - $percent));
            } else {
                // Stack is empty
                $val = 100;
            }
        }

        $val -= 9; // allow room for phases
        $val += 3 * (self::getGameStateValue('current_phase') % 4); // 3% each phase

        return $val;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    function getPlayersInOrder()
    {
	$result = array();

	$players = self::loadPlayersBasicInfos();
	$next_player = self::createNextPlayerTable(array_keys($players));

	$player_id = self::getCurrentPlayerId();
	for ($i=0; $i<count($players); $i++)
	{
	    $result[] = $player_id;
	    $player_id = $next_player[$player_id];
	}

	return $result;
    }

    protected function dbGetScore($player_id)
    {
	return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }

    protected function dbIncScore($player_id, $inc)
    {
	$cnt = $this->dbGetScore($player_id);
	if ($inc != 0)
	{
	    $cnt += $inc;
	    $this->DbQuery("UPDATE player SET player_score=$cnt WHERE player_id='$player_id'");
	}
	return $cnt;
    }
    protected function dbGetRubles($player_id)
    {
	return $this->getUniqueValueFromDB("SELECT player_score_aux FROM player WHERE player_id='$player_id'");
    }

    protected function dbIncRubles($player_id, $inc)
    {
	$cnt = $this->dbGetRubles($player_id);
	if ($inc != 0)
	{
	    $cnt += $inc;
	    $this->DbQuery("UPDATE player SET player_score_aux=$cnt WHERE player_id='$player_id'");
	}
	return $cnt;
    }

    function getCardInfo($card)
    {
        return $this->card_types[$card['type_arg']];
    }

    function getCardInfoById($card_id)
    {
        $card = $this->cards->getCard($card_id);
        return $this->getCardInfo($card);
    }

    function getCardName($card)
    {
        return $this->getCardInfo($card)['card_name'];
    }

    function getCardCost($card_id, $row, $trade_id=-1)
    {
	$card = $this->cards->getCard($card_id);
	$card_info = $this->getCardInfo($card);
	$cost = $card_info['card_cost'] - $row;

	$player_id = self::getActivePlayerId();
	$player_cards = $this->cards->getCardsInLocation('table', $player_id);

	foreach ($player_cards as $pcard) {
	    // Same card already owned
	    if ($pcard['type_arg'] == $card['type_arg']) {
		$cost--;
	    }

	    // Carpenter Workshop
	    if ($pcard['type_arg'] == CARD_CARPENTER_WORKSHOP && 
		$this->isBuilding($card))
	    {
		$cost--;
	    }

	    // Gold Smelter
	    if ($pcard['type_arg'] == CARD_GOLD_SMELTER &&
		$this->isAristocrat($card))
	    {
		$cost--;
	    }
	}

	if ($trade_id >= 0) {
	    $trade_info = $this->getCardInfoById($trade_id);
	    $cost -= $trade_info['card_value'];
	}

	return max($cost, 1);
    }

    function isCardType($card, $type)
    {
	$card_info = $this->getCardInfo($card);
	$is_type = $card['type'] == $type;
	$is_trade_type = ($card['type'] == PHASE_TRADING && $card_info['card_trade_type'] == $type);
	return ($is_type || $is_trade_type);
    }

    function isWorker($card) { return $this->isCardType($card, PHASE_WORKER); }
    function isBuilding($card) { return $this->isCardType($card, PHASE_BUILDING); }
    function isAristocrat($card) { return $this->isCardType($card, PHASE_ARISTOCRAT); }
    function isTrading($card) { return $card['type'] == PHASE_TRADING; }

    function scorePhase($phase)
    {
	if ($phase == PHASE_TRADING) {
	    return; // no scoring
	}

	$players = self::loadPlayersBasicInfos();
	$scores = array();
	foreach ($players as $player_id => $player) {
	    $points = 0;
	    $rubles = 0;

	    $taxman = false;
	    $workers = 0;

	    $theater = false;
	    $aristocrats = 0;

	    $cards = $this->cards->getCardsInLocation('table', $player_id);
	    foreach ($cards as $card) {
		if ($this->isCardType($card, $phase)) {
                    if ($card['type_arg'] == CARD_OBSERVATORY) {
                        // Observatory - do not score if used
                        $obs = $this->getObservatory($card['id']);
                        if ($obs['used']) continue;
                    }

		    $card_info = $this->getCardInfo($card);
		    $points += $card_info['card_points'];
		    $rubles += $card_info['card_rubles'];

		    if ($card['type_arg'] == CARD_TAX_MAN) {
			$taxman = true;
		    } else if ($card['type_arg'] == CARD_MARIINSKIJ_THEATER) {
			$theater = true;
		    }
		}

		if ($this->isWorker($card)) {
		    $workers++;
		} else if ($this->isAristocrat($card)) {
		    $aristocrats++;
		}
	    }

	    if ($taxman) {
		$rubles += $workers;
	    }
	    if ($theater) {
		$rubles += $aristocrats;
	    }

	    $scores[$player_id] = self::dbIncScore($player_id, $points);
            self::incStat($points, 'points_total', $player_id);
            self::incStat($points, 'points_' . $phase, $player_id);

	    self::dbIncRubles($player_id, $rubles);
            self::incStat($rubles, 'rubles_total', $player_id);
            self::incStat($rubles, 'rubles_' . $phase, $player_id);

	    $msg = clienttranslate('${player_name} earns ${rubles} Ruble(s) and ${points} Point(s)');
	    self::notifyAllPlayers('scorePhase', $msg, array(
		'player_id' => $player_id,
		'player_name' => $player['player_name'],
		'points' => $points,
		'rubles' => $rubles
	    ));
	}

	self::notifyAllPlayers('newScores', "", array(
	    'scores' => $scores
	));
    }

    function finalScoring()
    {
	$players = self::loadPlayersBasicInfos();
        $scores = array();
	foreach ($players as $player_id => $player) {
	    $table = $this->cards->getCardsInLocation('table', $player_id);
	    $aristocrats = array();
	    foreach ($table as $card) {
		if ($this->isAristocrat($card)) {
		    $aristocrats[] = $card['type_arg'];
		}
	    }

	    $num_ari = count(array_unique($aristocrats));
	    $points_ari = min(55, $num_ari * ($num_ari + 1) / 2);
	    self::dbIncScore($player_id, $points_ari);
            self::setStat($points_ari, 'points_aristocrats_end', $player_id);

	    $msg = clienttranslate('Final scoring: ${player_name} earns ${points_ari} point(s) for ${num_ari} aristocrat type(s)');
	    self::notifyAllPlayers('message', $msg, array(
		'player_name' => $player['player_name'],
		'points_ari' => $points_ari,
		'num_ari' => $num_ari,
	    ));

	    $num_rubles = self::dbGetRubles($player_id);
	    $points_rubles = intdiv($num_rubles, 10);
	    self::dbIncScore($player_id, $points_rubles);
            self::setStat($points_rubles, 'points_rubles_end', $player_id);

	    $msg = clienttranslate('Final scoring: ${player_name} earns ${points_rubles} point(s) for ${num_rubles} ruble(s)');
	    self::notifyAllPlayers('message', $msg, array(
		'player_name' => $player['player_name'],
		'points_rubles' => $points_rubles,
		'num_rubles' => $num_rubles,
	    ));

	    $num_hand = count($this->cards->getPlayerHand($player_id));
	    $points_hand = -5 * $num_hand;
	    $scores[$player_id] = self::dbIncScore($player_id, $points_hand);
            self::setStat($points_hand, 'points_hand_end', $player_id);

	    $msg = clienttranslate('Final scoring: ${player_name} loses ${points_hand} points for ${num_hand} card(s) in hand');
	    self::notifyAllPlayers('message', $msg, array(
		'player_name' => $player['player_name'],
		'points_hand' => $points_hand,
		'num_hand' => $num_hand,
	    ));

	}

	self::notifyAllPlayers('newScores', "", array('scores' => $scores));
    }

    function shiftCardsRight()
    {
	$num_cards = 0;
	foreach (array(BOTTOM_ROW, TOP_ROW) as $row) {
	    $board = $this->cards->getCardsInLocation($row, null, 'location_arg');
	    if (count($board) == 0) {
		continue;
	    }

	    $shifted = array();
	    foreach ($board as $card) {
		$loc = $card['location_arg'];
		$shifted[$loc] = $num_cards;
		$this->cards->moveCard($card['id'], $row, $num_cards);
		$num_cards++;
	    }

	    if ($row == BOTTOM_ROW) {
		$row_num = 1;
	    } else {
		$row_num = 0;
	    }

	    self::notifyAllPlayers('shiftRight', "", array(
		'columns' => $shifted,
		'row' => $row_num
	    ));
	}

	return $num_cards;
    }

    function shiftCardsDown()
    {
	$board = $this->cards->getCardsInLocation(TOP_ROW);
	if (count($board) == 0) {
	    return;
	}

	foreach ($board as $card) {
	    $this->cards->moveCard($card['id'], BOTTOM_ROW, $card['location_arg']);
	}

	self::notifyAllPlayers('shiftDown', "", array(
	    'columns' => array_column($board, 'location_arg')
	));
    }

    function discardBottomRow()
    {
	$discard = array();
	$board = $this->cards->getCardsInLocation(BOTTOM_ROW);
	foreach ($board as $card) {
	    $this->cards->playCard($card['id']);
	    $discard[] = array('col' => $card['location_arg'], 'row' => 1);
	}

	self::notifyAllPlayers('discard', "", array(
	    'cards' => $discard
	));
    }

    function getObservatory($card_id)
    {
        if ($card_id == self::getGameStateValue('observatory_0_id')) {
            $id = 0;
        } else if ($card_id == self::getGameStateValue('observatory_1_id')) {
            $id = 1;
        } else {
            throw new feException("Invalid Observatory ID");
        }

        return array(
            'id' => $id,
            'used' => self::getGameStateValue('observatory_' . $id . '_used')
        );
    }

    function isHandFull($player_id)
    {
        $max_hand = 3;
	$warehouse = $this->cards->getCardsOfTypeInLocation(
	    PHASE_BUILDING, CARD_WAREHOUSE, 'table', $player_id);
	if (count($warehouse) == 1) {
	    $max_hand++;
	}
        // Can be greater if player used and then displaced the Warehouse
        return count($this->cards->getPlayerHand($player_id)) >= $max_hand;
    }

    function getTrades($card, $cost, $player_id, &$trades)
    {
        $has_trade = false;
        $card_info = $this->getCardInfo($card);
	$cards = $this->cards->getCardsInLocation('table', $player_id);
        $rubles = self::dbGetRubles($player_id);
        foreach ($cards as $p_card) {
            $p_info = $this->getCardInfo($p_card);
            if ($card_info['card_trade_type'] != $p_info['card_type']) {
                continue; // Not correct trading type
            }
            if ($card_info['card_trade_type'] == PHASE_WORKER &&
                $card_info['card_worker_type'] != $p_info['card_worker_type'] &&
                $p_info['card_worker_type'] != WORKER_ALL)
            {
                continue; // Not correct worker type
            }
            if ($p_card['type_arg'] == CARD_OBSERVATORY) {
                $obs = $this->getObservatory($p_card['id']);
                if ($obs['used']) {
                    continue; // Observatory card has been used
                }
            }

            $has_trade = true; // At least one valid card, ignoring cost

            if ($cost - $p_info['card_value'] > $rubles) {
                continue; // Not enough value/rubles
            }

            $trades[] = $p_card['id'];
        }

        return $has_trade;
    }

    function hasTrades($card, $cost, $player_id)
    {
        $trades = array();
        $this->getTrades($card, $cost, $player_id, $trades);
        return count($trades) > 0;
    }

    function getSelectedCardOptions($card_id, $card_row)
    {
        // Get card details and adjusted cost
	$card = $this->cards->getCard($card_id);
	$cost = $this->getCardCost($card_id, $card_row);
        $player_id = self::getActivePlayerId();

        // Determine if player can buy (with trade if needed)
        $can_buy = true;
        if ($this->isTrading($card)) {
            if (!$this->hasTrades($card, $cost, $player_id)) {
                $can_buy = false;
            }
        } else if ($cost > self::dbGetRubles($player_id)) {
            $can_buy = false;
        }

	return array(
	    'card_name' => $this->getCardName($card),
            'cost' => $cost,
            'player_id' => $player_id,
            'can_add' => !$this->isHandFull($player_id),
            'can_buy' => $can_buy
	);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function selectCard($row, $loc_arg)
    {
	self::checkAction('selectCard');
	if ($row == 0) {
	    $loc = TOP_ROW;
	} else {
	    $loc = BOTTOM_ROW;
	}
	$cards = $this->cards->getCardsInLocation($loc, $loc_arg);

	if (count($cards) != 1)
	    throw new feException("Impossible move");

        $card = array_shift($cards);
	self::setGameStateValue("selected_card", $card['id']);
	self::setGameStateValue("selected_row", $row);

	$this->gamestate->nextState('selectCard');
    }

    function addCard()
    {
	self::checkAction('addCard');

	$card_id = self::getGameStateValue("selected_card");
	$card_row = self::getGameStateValue("selected_row");
	if ($card_id < 0 || $card_row < 0)
	    throw new feException("Impossible move");

        if ($this->isHandFull(self::getActivePlayerId()))
	    throw new BgaUserException(self::_("Your hand is full"));

	$dest = 'hand';
	$notif = 'addCard';
	$msg = clienttranslate('${player_name} adds ${card_name} to their hand');
	$this->cardAction($card_id, $card_row, 0, $dest, $notif, $msg);
	$this->gamestate->nextState('addCard');
    }

    function buyCard()
    {
	self::checkAction('buyCard');

	$card_id = self::getGameStateValue("selected_card");
	$card_row = self::getGameStateValue("selected_row");
	if ($card_id < 0 || $card_row < 0)
	    throw new feException("Impossible move");

	if ($this->isTrading($this->cards->getCard($card_id))) {
	    $this->gamestate->nextState('tradeCard');
	    return;
	}

	$card_cost = $this->getCardCost($card_id, $card_row);

	$player_id = self::getActivePlayerId();
	$rubles = self::dbGetRubles($player_id);
	if ($card_cost > $rubles)
	    throw new BgaUserException(self::_("You do not have enough rubles"));

	$dest = 'table';
	$notif = 'buyCard';
	$msg = clienttranslate('${player_name} buys ${card_name} for ${card_cost} Ruble(s)');
	$this->cardAction($card_id, $card_row, $card_cost, $dest, $notif, $msg);
	$this->gamestate->nextState('buyCard');
    }

    function playCard($card_id)
    {
	self::checkAction('playCard');

	if ($this->isTrading($this->cards->getCard($card_id))) {
	    self::setGameStateValue("selected_card", $card_id);
	    self::setGameStateValue("selected_row", 0);
	    $this->gamestate->nextState('tradeCardHand');
	    return;
	}

	$card_cost = $this->getCardCost($card_id, 0);

	$player_id = self::getActivePlayerId();
	$rubles = self::dbGetRubles($player_id);
	if ($card_cost > $rubles)
	    throw new BgaUserException(self::_("You do not have enough rubles"));

	$dest = 'table';
	$notif = 'playCard';
	$msg = clienttranslate('${player_name} plays ${card_name} from their hand for ${card_cost} Ruble(s)');
	$this->cardAction($card_id, 0, $card_cost, $dest, $notif, $msg);
	$this->gamestate->nextState('playCard');
    }

    function tradeCard($trade_id)
    {
	self::checkAction('tradeCard');

	// Ensure card already selected
	$card_id = self::getGameStateValue("selected_card");
	$card_row = self::getGameStateValue("selected_row");
	if ($card_id < 0 || $card_row < 0)
	    throw new feException("Impossible move");

	// Ensure card and trade exist
	$card = $this->cards->getCard($card_id); // trading card to buy
	$trade_card = $this->cards->getCard($trade_id); // card to be traded away
	if ($card == null || $trade_card == null)
	    throw new feException("Impossible card id");

	// Verify cards are of correct type to trade
	$card_info = $this->getCardInfo($card);
	$trade_info = $this->getCardInfo($trade_card);
	if ($card_info['card_trade_type'] != $trade_info['card_type'] ||
	    ($trade_info['card_type'] == PHASE_WORKER &&
            $card_info['card_worker_type'] != $trade_info['card_worker_type'] &&
            $trade_info['card_worker_type'] != WORKER_ALL))
	{
	    throw new BgaUserException(self::_("Wrong type of card to displace"));
	}

        // Check if trading used Observatory
        if ($trade_card['type_arg'] == CARD_OBSERVATORY) {
            $obs = $this->getObservatory($trade_id);
            if ($obs['used'])
                throw new BgaUserException(self::_("You cannot displace an Observatory after using it"));
        }

	// Compute cost and ensure player can pay it
	$card_cost = $this->getCardCost($card_id, $card_row, $trade_id);
	$player_id = self::getActivePlayerId();
	$rubles = self::dbGetRubles($player_id);
	if ($card_cost > $rubles)
	    throw new BgaUserException(self::_("You do not have enough rubles"));

	// Notify message details
	$card_idx = $card['type_arg'];
	$card_loc = $card['location_arg'];
	if ($card['location'] == 'hand') {
	    //play
	    $card_row = ROW_HAND; // signal client card is in hand
	    $msg = clienttranslate('${player_name} plays ${card_name} from their hand, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else if ($card['location'] == 'obs_tmp') {
            //observatory
            $card_row = ROW_OBSERVATORY; // signal client card is observatory pick
	    $msg = clienttranslate('${player_name} buys ${card_name}, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
	    //buy
	    $msg = clienttranslate('${player_name} buys ${card_name}, displacing ${trade_name}, for ${card_cost} Ruble(s)');
	}

	// Pay cost and take card
	// Do this last to ensure notify information is accurate for pre-move
	$player_id = self::getActivePlayerId();
	$this->dbIncRubles($player_id, -$card_cost);
        self::incStat($card_cost, 'rubles_spent', $player_id);
	$this->cards->playCard($trade_id);
        self::incStat(1, 'cards_traded', $player_id);
	$this->cards->moveCard($card_id, 'table', $player_id);
        self::incStat(1, 'cards_bought', $player_id);

	self::notifyAllPlayers('tradeCard', $msg, array(
	    'player_id' => $player_id,
	    'player_name' => self::getActivePlayerName(),
	    'card_name' => $this->getCardName($card),
	    'card_id' => $card_id,
	    'card_idx' => $card_idx,
	    'card_loc' => $card_loc,
	    'card_row' => $card_row,
	    'card_cost' => $card_cost,
	    'trade_id' => $trade_id,
	    'trade_name' => $this->getCardName($trade_card)
	));

	// Reset card selection and pass counter globals
	self::setGameStateValue("selected_card", -1);
	self::setGameStateValue("selected_row", -1);
	self::setGameStateValue("activated_observatory", -1);
	self::setGameStateValue("num_pass", 0);
        self::incStat(1, 'actions_taken', $player_id);

	$this->gamestate->nextState('tradeCard');
    }

    protected function cardAction($card_id, $card_row, $card_cost, $dest, $notif, $msg)
    {
	$card = $this->cards->getCard($card_id);
	$card_idx = $card['type_arg'];

	// Pay cost and take card
	$player_id = self::getActivePlayerId();
	$this->dbIncRubles($player_id, -$card_cost);
        self::incStat($card_cost, 'rubles_spent', $player_id);
	$this->cards->moveCard($card_id, $dest, $player_id);

        if ($dest == 'table') {
            self::incStat(1, 'cards_bought', $player_id);
        } else if ($dest == 'hand') {
            self::incStat(1, 'cards_added', $player_id);
        }

	self::notifyAllPlayers($notif, $msg, array(
	    'player_id' => $player_id,
	    'player_name' => self::getActivePlayerName(),
	    'card_name' => $this->getCardName($card),
	    'card_id' => $card_id,
	    'card_idx' => $card_idx,
	    'card_loc' => $card['location_arg'],
	    'card_row' => $card_row,
	    'card_cost' => $card_cost
	));

	// Reset card selection and pass counter globals
	self::setGameStateValue("selected_card", -1);
	self::setGameStateValue("selected_row", -1);
	self::setGameStateValue("activated_observatory", -1);
	self::setGameStateValue("num_pass", 0);
        self::incStat(1, 'actions_taken', $player_id);
    }

    function cancelSelect()
    {
	self::checkAction('cancel');

	// Player clicks cancel after selecting card
	// Reset globals for card selection
	self::setGameStateValue("selected_card", -1);
	self::setGameStateValue("selected_row", -1);
	$this->gamestate->nextState("cancel");
    }

    function pass()
    {
	self::checkAction('pass');

	$num_pass = self::incGameStateValue('num_pass', 1);
	self::notifyAllPlayers('message', clienttranslate('${player_name} passes'), array(
	    'player_name' => self::getActivePlayerName()
	));

	if ($num_pass == self::getPlayersNumber()) {
	    // All players pass in turn => next phase
	    // Reset pass counter for next phase
	    self::setGameStateValue("num_pass", 0);
	    $this->gamestate->nextState('allPass');
	} else {
	    // One or more players left to pass => next player
	    $this->gamestate->nextState('pass');
	}
    }

    function buyPoints($points)
    {
	self::checkAction('buyPoints');

	$player_id = self::getCurrentPlayerId();
        $max_points = 0;
	$pubs = $this->cards->getCardsOfTypeInLocation(
	    PHASE_BUILDING, CARD_PUB, 'table');
	foreach ($pubs as $card) {
            if ($card['location_arg'] == $player_id) {
                $max_points += 5;
            }
        }

	if ($points < 0 || $points > $max_points)
	    throw new feException("Impossible pub buy");

	if ($points > 0) {
	    $rubles = self::dbGetRubles($player_id);
	    $cost = $points * 2;
	    if ($cost > $rubles)
		throw new BgaUserException(self::_("You do not have enough rubles"));

	    $this->dbIncRubles($player_id, -$cost);
            self::incStat($cost, 'rubles_spent', $player_id);
	    $this->dbIncScore($player_id, $points);
            self::incStat($points, 'pub_points', $player_id);

	    $msg = clienttranslate('${player_name} uses the Pub to buy ${points} Point(s) for ${cost} Rubles');
	    self::notifyAllPlayers('buyPoints', $msg, array(
		'player_id' => $player_id,
		'player_name' => self::getCurrentPlayerName(),
		'points' => $points,
		'cost' => $cost
	    ));
	} else {
	    self::notifyAllPlayers('message', clienttranslate('${player_name} declines to use the Pub bonus'), array(
		'player_name' => self::getCurrentPlayerName()
	    ));
	}
	
	$this->gamestate->setPlayerNonMultiactive($player_id, 'nextPhase');
    }

    function useObservatory($card_id)
    {
        self::checkAction('useObservatory');

        // Verify that
        // 1. the card exists and is the observatory,
        // 2. it is owned by the player
        // 3. it is the building phase
        $player_id = self::getActivePlayerId();
        $card = $this->cards->getCard($card_id);
	$phase = self::getGameStateValue('current_phase') % 4;
        if ($card == null || $card['type_arg'] != CARD_OBSERVATORY ||
            $card['location_arg'] != $player_id || $card['location'] != 'table')
        {
            throw new feException("Invalid Observatory play");
        }

        $obs = $this->getObservatory($card_id);
        if ($obs['used'] || $this->phases[$phase] != PHASE_BUILDING)
            throw new BgaUserException(self::_("You cannot use the Observatory right now"));

        self::setGameStateValue("activated_observatory", $obs['id']);
        $this->gamestate->nextState("useObservatory");
    }

    function drawObservatoryCard($deck)
    {
        self::checkAction('drawObservatoryCard');

	$obs_id = self::getGameStateValue("activated_observatory");
        if ($obs_id != 0 && $obs_id != 1)
            throw new feException("Impossible Obseratory draw");

        $num_cards = $this->cards->countCardInLocation($deck);
        if ($num_cards == 0) {
            throw new BgaUserException(self::_("Card stack is empty"));
        } else if ($num_cards == 1) {
            throw new BgaUserException(self::_("You cannot draw the last card"));
        }

        $player_id = self::getActivePlayerId();
        $card = $this->cards->pickCardForLocation($deck, 'obs_tmp', $player_id);
        if ($card == null || $this->cards->countCardInLocation('obs_tmp') != 1)
            throw new feException("Impossible Observatory draw");

        $phase = explode('_', $deck)[1];

        $msg = clienttranslate('${player_name} uses Observatory to draw ${card_name} from the ${phase} stack');
        self::notifyAllPlayers('message', $msg, array(
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->getCardName($card),
            'phase' => $phase
        ));

        self::setGameStateValue('observatory_' . $obs_id . '_used', 1);
        self::incStat(1, 'observatory_draws', $player_id);
        $this->gamestate->nextState("drawCard");
    }
    
    function obsBuy()
    {
	self::checkAction('buyCard');

        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory buy");

        $card = array_shift($cards);

	if ($this->isTrading($card)) {
	    self::setGameStateValue("selected_card", $card['id']);
	    self::setGameStateValue("selected_row", 0);
	    $this->gamestate->nextState('tradeCard');
	    return;
	}

	$card_cost = $this->getCardCost($card['id'], 0);

	$rubles = self::dbGetRubles($player_id);
	if ($card_cost > $rubles)
	    throw new BgaUserException(self::_("You do not have enough rubles"));

	$dest = 'table';
	$notif = 'buyCard';
	$msg = clienttranslate('${player_name} buys ${card_name} for ${card_cost} Ruble(s)');
	$this->cardAction($card['id'], ROW_OBSERVATORY, $card_cost, $dest, $notif, $msg);
	$this->gamestate->nextState('buyCard');
    }

    function obsAdd()
    {
	self::checkAction('addCard');

        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory add");

        if ($this->isHandFull($player_id))
	    throw new BgaUserException(self::_("Your hand is full"));

        $card = array_shift($cards);
	$dest = 'hand';
	$notif = 'addCard';
	$msg = clienttranslate('${player_name} adds ${card_name} to their hand');
	$this->cardAction($card['id'], ROW_OBSERVATORY, 0, $dest, $notif, $msg);
	$this->gamestate->nextState('addCard');
    }

    function obsDiscard()
    {
        self::checkAction('discard');

        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory discard");

        $card = array_shift($cards);
        $this->cards->playCard($card['id']);

        // Reuse end round discard notif arg
        $location = array();
        $location[] = array('row' => ROW_OBSERVATORY);

        $msg = clienttranslate('${player_name} discards ${card_name}');
	self::notifyAllPlayers('discard', $msg, array(
	    'player_name' => self::getActivePlayerName(),
	    'card_name' => $this->getCardName($card),
	    'cards' => $location
	));

	self::setGameStateValue("activated_observatory", -1);
	self::setGameStateValue("num_pass", 0);
        self::incStat(1, 'actions_taken', $player_id);
        $this->gamestate->nextState('discard');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPlayerTurn()
    {
        return array();
    }

    function argSelectCard()
    {
	// Selected card location are global variables
	$card_id = self::getGameStateValue("selected_card");
	$card_row = self::getGameStateValue("selected_row");
	if ($card_id < 0 || $card_row < 0)
	    //throw new feException("Impossible state");//TODO ??
	    return array();

	$card = $this->cards->getCard($card_id);
        $opts = $this->getSelectedCardOptions($card_id, $card_row);
        $opts['card_id'] = $card_id;
        $opts['row'] = $card_row;
        $opts['col'] = $card['location_arg'];

        return $opts;
    }

    function argTradeCard()
    {
	// Selected card location are global variables
	$card_id = self::getGameStateValue("selected_card");
	$card_row = self::getGameStateValue("selected_row");
	if ($card_id < 0 || $card_row < 0)
	    //throw new feException("Impossible state"); //TODO ???
	    return array();

	// Get card details and adjusted cost
	$card = $this->cards->getCard($card_id);
	$cost = $this->getCardCost($card_id, $card_row);

        $trades = array();
        $player_id = self::getActivePlayerId();

        if ($this->isTrading($card)) {
            $has_trade = $this->getTrades($card, $cost, $player_id, $trades);

            // Must have valid card and enough rubles to make trade
            if (count($trades) == 0) {
                if ($has_trade) {
                    throw new BgaUserException(self::_('You do not have enough rubles to trade'));
                } else {
                    throw new BgaUserException(self::_('You do not have any valid cards to trade'));
                }
            }
        }

	return array(
	    'card_name' => $this->getCardName($card),
            'cost' => $cost,
            'player_id' => $player_id,
            'card_id' => $card['id'],
            'row' => $card_row,
            'col' => $card['location_arg'],
            'trades' => $trades
	);
    }

    function argChooseObservatory()
    {
        // Send card drawn with Observatory
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory recall");

        $card = array_shift($cards);
        $obs_id = self::getGameStateValue("activated_observatory");
        $opts = $this->getSelectedCardOptions($card['id'], 0);
        $opts['card'] = $card;
        $opts['obs_id'] = self::getGameStateValue('observatory_' . $obs_id . '_id');

        return $opts;
    }

    /*
     * Arguments for STATE_USE_PUB
     * Returns an array of player(s) that own one or more Pub cards where
     * key: player_id => value: maximum number of points they can buy
     * based on number or Pub cards (1 or 2) and available rubles.
     */
    function argUsePub()
    {
        $players = array();
	$pubs = $this->cards->getCardsOfTypeInLocation(
	    PHASE_BUILDING, CARD_PUB, 'table');

        // Determine which players own the Pubs
	foreach ($pubs as $card) {
            $player_id = $card['location_arg'];
            if (key_exists($player_id, $players)) {
	        $players[$player_id] += 5;
            } else {
	        $players[$player_id] = 5;
            }
	}

        // Determine available rubles for Pub owner(s)
        foreach ($players as $player_id => $points) {
            $rubles = self::dbGetRubles($player_id);
            $poss_buys = intdiv($rubles, 2);
            $players[$player_id] = min($points, $poss_buys);
        }

	return $players;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPlayer()
    {
	$player_id = self::activeNextPlayer();
	self::giveExtraTime($player_id);
	$this->gamestate->nextState('nextTurn');
    }

    function stScorePhase()
    {
	// Get phase status
	$current_phase = self::getGameStateValue('current_phase') % 4;
	$next_phase = self::incGameStateValue('current_phase', 1) % 4;
	$new_round = ($next_phase == 0);

	// Check if last phase of final round just finished
	if ($new_round && self::getGameStateValue("last_round")) {
	    $this->finalScoring();
	    $this->gamestate->nextState('endGame');
	    return;
	}

	// Score phase just completed
	$phase = $this->phases[$current_phase];
	$this->scorePhase($phase);

	if ($phase == PHASE_BUILDING) {
	    // Allow pub to be used if owned
	    $this->gamestate->nextState('usePub');
	} else {
	    $this->gamestate->nextState('nextPhase');
	}
    }

    function stUsePub()
    {
	// Allow any players that own the pub to use it
	$players = array();
	$pubs = $this->cards->getCardsOfTypeInLocation(
	    PHASE_BUILDING, CARD_PUB, 'table');
	foreach ($pubs as $card) {
	    $players[] = $card['location_arg'];
	}

	$this->gamestate->setPlayersMultiactive($players, 'nextPhase', true);
    }

    function stNextPhase()
    {
	// Get phase (already incremented)
	$next_phase = self::getGameStateValue('current_phase') % 4;
	$phase = $this->phases[$next_phase];

	// Handle new round (Trading -> Worker)
	if ($next_phase == 0) {
	    // Discard bottom cards and move top row down
	    $this->discardBottomRow();
	    $this->shiftCardsDown();

	    // Rotate starting player tokens
	    $tokens = array();
	    $players = self::loadPlayersBasicInfos();
	    $next_player = self::createNextPlayerTable(array_keys($players));
	    foreach ($this->phases as $token_phase) {
		$token = "starting_player_" . $token_phase;
		$player_id = self::getGameStateValue($token);
		self::setGameStateValue($token, $next_player[$player_id]);
                $tokens[$token_phase] = array(
                    'current' => $player_id,
                    'next' => $next_player[$player_id]
                );
	    }

            // Reset any used Observatory cards
            self::setGameStateValue('observatory_0_used', 0);
            self::setGameStateValue('observatory_1_used', 0);

            self::notifyAllPlayers('newRound', "", array('tokens' => $tokens));
	}

	// Move all cards on board as far right as possible
	$num_cards = $this->shiftCardsRight();

	// Draw up to 8 new cards from current deck
	$new_cards = array();
	for ($num_cards; $num_cards<8; $num_cards++) {
	    $card = $this->cards->pickCardForLocation('deck_' . $phase, TOP_ROW, $num_cards);
	    if ($card == null) // empty deck
		break;

	    $new_cards[$num_cards] = $card['type_arg'];
	}

	// Check if deck was emptied to trigger final round
	if ($this->cards->countCardInLocation('deck_' . $phase) <= 0) {
	    if (!self::getGameStateValue("last_round")) {
		self::setGameStateValue("last_round", 1);
		$msg = clienttranslate('${phase} deck is empty, this is the final round');
		self::notifyAllPlayers('lastRound', $msg, array(
		    'phase' => $phase
		));
	    }
	}

	// Activate starting player (_not_ next player) for next phase
	$starting_player = self::getGameStateValue("starting_player_" . $phase);
	$this->gamestate->changeActivePlayer($starting_player);
	self::giveExtraTime($starting_player);

	$msg = clienttranslate('${phase} phase begins, starting with ${player_name}');
	self::notifyAllPlayers('nextPhase', $msg, array(
	    'player_name' => self::getActivePlayerName(),
	    'phase' => $phase,
	    'cards' => $new_cards
	));

	$this->gamestate->nextState('nextTurn');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            if ($statename == "playerTurn") {
                // No special action, just pass below
                $pass = true; // Empty expression just to capture state
            } else if ($statename == "selectCard" ||
                $statename == "tradeCard" ||
                $statename == "tradeCardHand" ||
                $statename == "useObservatory")
            {
                // Clear card selection
                // No notify as UI change is only on active (zombie) player
	        self::setGameStateValue("selected_card", -1);
	        self::setGameStateValue("selected_row", -1);
            } else if ($statename == "chooseObservatory" ||
                    $statename == "tradeObservatory")
            {
                // Clear card selection
	        self::setGameStateValue("selected_card", -1);
	        self::setGameStateValue("selected_row", -1);
	        self::setGameStateValue("activated_observatory", -1);

                // Discard drawn card
                $cards = $this->cards->getCardsInLocation('obs_tmp', $active_player);
                if ($cards != null && count($cards) == 1) {
                    $card = array_shift($cards);
                    $this->cards->playCard($card['id']);

                    // Notify client to clear UI for other players
                    $msg = clienttranslate('${card_name} is discarded automatically');
                    self::notifyAllPlayers('discard', $msg, array(
                        'card_name' => $this->getCardName($card),
                        'cards' => array(array('row' => ROW_OBSERVATORY))
                    ));
                }
            } else {
                throw new feException("Zombie mode not supported at this game state: ".$statename);
            }

            // Pass
            $num_pass = self::incGameStateValue('num_pass', 1);
            if ($num_pass == self::getPlayersNumber()) {
                // Zombie is last player to pass => next phase
                // Reset pass counter for next phase
                self::setGameStateValue("num_pass", 0);
                $this->gamestate->nextState('zombieAllPass');
            } else {
                // One or more players left to pass => next player
                $this->gamestate->nextState('zombiePass');
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            // Only multi state is Pub buy, and this is the correct action
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            
            return;
        }

        throw new feException("Zombie mode not supported at this game state: ".$statename);
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if($from_version <= 1404301345)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        if($from_version <= 1405061421)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB($sql);
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
