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
            "num_pass" => 16,              // number of players that have consecutively passed in this phase
            "current_phase" => 17,         // current phase number, always increasing
            "last_round" => 18,            // 1 if the end state has been triggered in the current round
            "observatory_0_id" => 19,      // card_id of first Observatory card
            "observatory_1_id" => 20,      // card_id of second Observatory card
            "observatory_0_used" => 21,    // 1 if first Observatory has been used this round
            "observatory_1_used" => 22,    // 1 if second Observatory has been used this round
            "activated_observatory" => 23, // index (0/1) of Observatory being actively used

            // Game options
            "show_player_rubles" => OPT_SHOW_RUBLES,
            "show_player_hands" => OPT_SHOW_HANDS,
            "version" => OPT_VERSION,
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
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Player rubles is tie breaker and so held in aux score
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_score_aux) VALUES ";
        $values = array();
        foreach($players as $player_id => $player)
        {
            $color = array_shift($default_colors);
            // start with 25 rubles
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."',25)";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue("num_pass", 0);
        self::setGameStateInitialValue("current_phase", 0);
        self::setGameStateInitialValue("last_round", 0);

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

        // Init game statistics
        self::initStat('table', "turns_number", 1); // count first turn
        self::initStat('table', "rounds_number", 1); // count first round
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
        foreach ($this->getCardInfos() as $idx => $card) {
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
        $this->drawCards($num_players * 2, 0, PHASE_WORKER);

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

        $result['version'] = $this->optEdition();
        
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        $sql = "SELECT player_id id, player_score score FROM player";
        $result['players'] = self::getCollectionFromDb($sql);
  
        // Get all cards on table and number in hand for each player
        $players = self::loadPlayersBasicInfos();
        $tables = array();
        $hands = array();
        $hand_size = array();
        $hand_type = array();
        $aristocrats = array();
        $income = array();
        foreach ($players as $player_id => $player)
        {
            $tables[$player_id] = $this->cards->getCardsInLocation('table', $player_id);
            $cards = $this->cards->getPlayerHand($player_id);
            if ($player_id == $current_player_id || $this->optShowHands()) {
                // Always send full hand details for current player
                // Send all others if game option is enabled
                $hands[$player_id] = $this->cards->getPlayerHand($player_id);
            }

            // Players always get to see how many cards and what types are in other hands
            $hand_size[$player_id] = count($this->cards->getPlayerHand($player_id));
            $hand_type[$player_id] = array();
            foreach ($cards as $card) {
                $hand_type[$player_id][] = $card['type'];
            }

            $aristocrats[$player_id] = $this->uniqueAristocrats($player_id);

            $income[$player_id] = $this->getIncome($player_id);
        }
        $result['player_tables'] = $tables;
        $result['player_hands'] = $hands;
        $result['player_hand_size'] = $hand_size;
        $result['player_hand_type'] = $hand_type;
        $result['aristocrats'] = $aristocrats;
        $result['income'] = $income;

        // Get number of rubles for current or all players
        // Separate query to avoid sending possible secret info in 'players' above
        if ($this->optShowRubles()) {
            // Option to see all player rubles enabled
            $sql = "SELECT player_id, player_score_aux FROM player";
            $result['rubles'] = self::getCollectionFromDb($sql, true);
        } else {
            // By default only own rubles visible
            $result['rubles'] = array(
                $current_player_id => self::dbGetRubles($current_player_id)
            );
        }

        // Current player automatically passing?
        $result['autopass'] = $this->dbGetAutoPass($current_player_id);

        // Number players already passed this turn
        $result['num_pass'] = $this->getGameStateValue('num_pass');
        // Player order to mark passing (needed for spectator when playerorder not set)
        $result['players_in_order'] = $this->getPlayersInOrder();

        // Get starting player tokens for each phase
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

        // Current phase
        $result['phase'] = $this->phases[self::getGameStateValue('current_phase') % 4];
        $result['last_round'] = self::getGameStateValue("last_round") == 1;

        // Cards on board
        $result[TOP_ROW] = $this->cards->getCardsInLocation(TOP_ROW);
        $result[BOTTOM_ROW] = $this->cards->getCardsInLocation(BOTTOM_ROW);

        // Cards counts for each deck
        $result['decks'] = $this->cards->countCardsInLocations();

        // Full card info used for tooltips
        $result['card_infos'] = $this->getCardInfos();

        // Observatory status
        $obs = array();
        for ($i=0; $i<2; $i++) {
            $obs[] = array(
                'id' => self::getGameStateValue('observatory_' . $i . '_id'),
                'used' => self::getGameStateValue('observatory_' . $i . '_used'),
            );
        }
        $result['observatory'] = $obs;

        // Constant value for identifying an Observatory
        $result['constants'] = array(
            'top_row' => 0,
            'bottom_row' => 1,
            'hand' => ROW_HAND,
            'observatory' => ROW_OBSERVATORY
        );

        $result['buyOnly'] = $this->opt2ndEdition() && self::getGameStateValue('current_phase') == 0;
  
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
        $oneStackEmpty = false;

        // Find percentage of smallest stack
        $counts = $this->cards->countCardsInLocations();
        foreach ($this->phases as $phase) {
            if (key_exists('deck_' . $phase, $counts)) {
                $count = $counts['deck_' . $phase];
                $percent = $count / $this->deck_size[$phase];
                $val = max($val, 91 * (1 - $percent));
                if ($count == 0) {
                    $oneStackEmpty = true;
                }
            } else {
                // Stack is empty
                $val = 91;
                $oneStackEmpty = true;
            }
        }
        // Add phase progress only if one stack is empty, avoid a drop in progress at the end of the game.
        if ($oneStackEmpty) {
            $val += 3 * (self::getGameStateValue('current_phase') % 4); // 3% each phase
        }

        return $val;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
     * Return an array of players in natural turn order starting
     * with the current player. This is used to build the player
     * tables in the same order as the player boards.
     *
     * While the starting player for each phase is determined by
     * cutom games rules, play will alway proceed in natural order.
     */
    function getPlayersInOrder()
    {
        $result = array();

        $players = self::loadPlayersBasicInfos();
        $next_player = self::getNextPlayerTable();
        $player_id = self::getCurrentPlayerId();

        // Check for spectator
        if (!key_exists($player_id, $players)) {
            $player_id = $next_player[0];
        }

        // Build array starting with current player
        for ($i=0; $i<count($players); $i++) {
            $result[] = $player_id;
            $player_id = $next_player[$player_id];
        }

        return $result;
    }

    /*
     * Return the current score of the given player
     */
    protected function dbGetScore($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }

    /*
     * Increment the score of the given player by the given amount
     */
    protected function dbIncScore($player_id, $inc)
    {
        $cnt = $this->dbGetScore($player_id);
        if ($inc != 0) {
            $cnt += $inc;
            $this->DbQuery("UPDATE player SET player_score=$cnt WHERE player_id='$player_id'");
        }
        return $cnt;
    }

    /*
     * Return the current number of rubles for a given player
     */
    protected function dbGetRubles($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT player_score_aux FROM player WHERE player_id='$player_id'");
    }

    /*
     * Increment the number of rubles of the given player by the given amount,
     * which can be negative
     */
    protected function dbIncRubles($player_id, $inc)
    {
        $cnt = $this->dbGetRubles($player_id);
        if ($inc != 0) {
            $cnt += $inc;
            $this->DbQuery("UPDATE player SET player_score_aux=$cnt WHERE player_id='$player_id'");
        }
        return $cnt;
    }

    /*
     * Comparison function for sorting cards by model,
     * which will naturally sort by cost as well
     */
    function compareCards($a, $b)
    {
        $infoA = $this->getCardInfo($a);
        $infoB = $this->getCardInfo($b);
        return $infoB['card_model'] - $infoA['card_model'];
    }
    
    /*
     * Return additional card information.
     * This information is stored outside of the deck in order to make
     * use of the standard Deck implementation and functions.
     */
    function getCardInfos()
    {
        if ($this->opt2ndEdition()) {
            return $this->card_infos2nd;
        }
        return $this->card_infos;
    }
    
    /*
     * Return additional card information for the given card.
     * This information is stored outside of the deck in order to make
     * use of the standard Deck implementation and functions.
     */
    function getCardInfo($card)
    {
        return $this->getCardInfos()[$card['type_arg']];
    }

    /*
     * Return additional card information for the given card ID.
     * See getCardInfo
     */
    function getCardInfoById($card_id)
    {
        $card = $this->cards->getCard($card_id);
        return $this->getCardInfo($card);
    }

    /*
     * Return the written name of the given card.
     * Convenience function mainly used for notifications.
     */
    function getCardName($card)
    {
        return $this->getCardInfo($card)['card_name'];
    }

    /*
     * Return the active player's adjusted cost for the given card ID
     * accounting for all possible reductions
     */
    function getCardCost($card_id, $row, $trade_id=-1)
    {
        // Get card details
        $card = $this->cards->getCard($card_id);
        $card_info = $this->getCardInfo($card);
        self::dump('$card_info', $card_info);
        $card_model = $card_info['card_model'];

        // -1 if taken from the lower row
        if ($row != 1) {
            // Other locations (e.g. hand, Observatory) give no discount
            $row = 0;
        }
        $cost = $card_info['card_cost'] - $row;

        $player_id = self::getActivePlayerId();
        $player_cards = $this->cards->getCardsInLocation('table', $player_id);

        foreach ($player_cards as $pcard) {
            // -1 for each copy of same card already owned
            $pcard_info = $this->getCardInfo($pcard);
            if ($card_model == $pcard_info['card_model']) {
                $cost--;
            }

            // -1 for blue buildings if player owns Carpenter Workshop
            if ($pcard['type_arg'] == CARD_CARPENTER_WORKSHOP && 
                $this->isBuilding($card))
            {
                $cost--;
            }

            // -1 for red aristocrats if player owns Gold Smelter
            if ($pcard['type_arg'] == CARD_GOLD_SMELTER &&
                $this->isAristocrat($card))
            {
                $cost--;
            }
        }

        // Trading card: subtract the base cost (value, see Potjomkin's Village)
        // of the displaced card
        if ($trade_id > 0) {
            $trade_info = $this->getCardInfoById($trade_id);
            $cost -= $trade_info['card_value'];
        }

        // Minimum cost is always 1
        return max($cost, 1);
    }

    /*
     * Return true if the given card is the same type as given,
     * including a trading card of the same color
     */
    function isCardType($card, $type)
    {
        $card_info = $this->getCardInfo($card);
        $is_type = $card['type'] == $type;
        $is_trade_type = ($card['type'] == PHASE_TRADING && $card_info['card_trade_type'] == $type);
        return ($is_type || $is_trade_type);
    }

    // Convenience functions of isCardType with each type
    function isWorker($card) { return $this->isCardType($card, PHASE_WORKER); }
    function isBuilding($card) { return $this->isCardType($card, PHASE_BUILDING); }
    function isAristocrat($card) { return $this->isCardType($card, PHASE_ARISTOCRAT); }
    function isTrading($card) { return $card['type'] == PHASE_TRADING; }

    /*
     * Count the number of different aristocrats the given player owns
     */
    function uniqueAristocrats($player_id)
    {
        // Get all aristocrat cards on player table
        $table = $this->cards->getCardsInLocation('table', $player_id);
        $aristocrats = array();
        foreach ($table as $card) {
            if ($this->isAristocrat($card)) {
                $aristocrats[] = $card['type_arg'];
            }
        }

        // Return unique count
        return count(array_unique($aristocrats));
    }

    /*
     * Compute the scoring potential for a given player/phase
     */
    function computeScoring($player_id, $phase)
    {
        $points = 0;
        $rubles = 0;

        $taxman = false;
        $workers = 0;
        $theater = false;
        $aristocrats = 0;

        $cards = $this->cards->getCardsInLocation('table', $player_id);
        foreach ($cards as $card) {
            // Only cards from the current phase are scored
            if ($this->isCardType($card, $phase)) {
                if ($card['type_arg'] == CARD_OBSERVATORY) {
                    // Observatory - do not score if used
                    $obs = $this->getObservatory($card['id']);
                    if ($obs['used']) continue;
                }

                $card_info = $this->getCardInfo($card);
                $points += $card_info['card_points'];
                $rubles += $card_info['card_rubles'];

                // These two special cards score based on other cards
                if ($card['type_arg'] == CARD_TAX_MAN) {
                    $taxman = true;
                } else if ($card['type_arg'] == CARD_MARIINSKIJ_THEATER) {
                    $theater = true;
                }
            }

            // Count cards for special scoring
            if ($this->isWorker($card)) {
                $workers++;
            } else if ($this->isAristocrat($card)) {
                $aristocrats++;
            }
        }

        // Score special cards
        if ($taxman) {
            $rubles += $workers;
        }
        if ($theater) {
            if ($this->opt2ndEdition()) {
                $points += $aristocrats;
            } else {
                $rubles += $aristocrats;
            }
        }

        return array($points, $rubles);
    }

    /*
     * Get total point and ruble income for a player by phase
     */
    function getIncome($player_id)
    {
        $phases = array(PHASE_WORKER, PHASE_BUILDING, PHASE_ARISTOCRAT);
        $points = array();
        $rubles = array();
        for ($i = 0; $i < 3; $i++) {
            list($pts, $rbl) = $this->computeScoring($player_id, $phases[$i]);
            $points[] = $pts;
            $rubles[] = $rbl;
        }

        return array(
            'points' => $points,
            'rubles' => $rubles,
        );
    }

    /*
     * Compute the scores at the end of the given phase
     */
    function scorePhase($phase)
    {
        if ($phase == PHASE_TRADING) {
            return; // no scoring after trading card phase
        }

        $players = self::loadPlayersBasicInfos();
        $scores = array();
        foreach ($players as $player_id => $player) {
            list($points, $rubles) = $this->computeScoring($player_id, $phase);

            // Update scores, stats, and log
            $scores[$player_id] = self::dbIncScore($player_id, $points); // score to report
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

        // Notify to update scores on client
        self::notifyAllPlayers('newScores', "", array(
            'scores' => $scores
        ));
    }

    /*
     * Compute end game scoring and set final results
     */
    function finalScoring()
    {
        $players = self::loadPlayersBasicInfos();
        $scores = array();
        $rubles = array();
        foreach ($players as $player_id => $player) {
            // Each different aristocrat (up to 10) is worth that many points
            // 1 = 1, 2 = 1+2 = 3, 3 = 1+2+3 = 6, ... 10 = 1+2+3+...+10 = 55
            // or more simply: n(n+1)/2
            $num_ari = $this->uniqueAristocrats($player_id);
            $points_ari = min(55, $num_ari * ($num_ari + 1) / 2);
            self::dbIncScore($player_id, $points_ari);
            self::setStat($points_ari, 'points_aristocrats_end', $player_id);

            $msg = clienttranslate('Final scoring: ${player_name} earns ${points_ari} Point(s) for ${num_ari} Aristocrat type(s)');
            self::notifyAllPlayers('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_ari' => $points_ari,
                'num_ari' => $num_ari,
            ));

            // 1 per 10 rubles, ignoring any remainder
            // Players trade these rubles for points
            $num_rubles = self::dbGetRubles($player_id);
            $points_rubles = intdiv($num_rubles, 10);
            $num_rubles = 10 * $points_rubles;
            self::dbIncScore($player_id, $points_rubles);
            $rubles[$player_id] = self::dbIncRubles($player_id, -1 * $num_rubles);
            self::setStat($points_rubles, 'points_rubles_end', $player_id);

            $msg = clienttranslate('Final scoring: ${player_name} earns ${points_rubles} Point(s) for ${num_rubles} Ruble(s)');
            self::notifyAllPlayers('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_rubles' => $points_rubles,
                'num_rubles' => $num_rubles,
            ));

            // -5 per card left in hand
            $num_hand = count($this->cards->getPlayerHand($player_id));
            $points_hand = -5 * $num_hand;
            $scores[$player_id] = self::dbIncScore($player_id, $points_hand); // set final score to report
            self::setStat($points_hand, 'points_hand_end', $player_id);

            $msg = clienttranslate('Final scoring: ${player_name} loses ${points_hand} Points for ${num_hand} card(s) in hand');
            self::notifyAllPlayers('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_hand' => $points_hand,
                'num_hand' => $num_hand,
            ));

        }

        self::notifyAllPlayers('newScores', "", array(
            'scores' => $scores,
            'rubles' => $rubles,
        ));
    }

    /*
     * Draw and sort a given number of cards from the given phase stack
     * onto the top row of the board, starting at the given location
     */
    function drawCards($nbr, $start_idx, $phase)
    {
        // Draw cards from phase stack
        $unsorted = $this->cards->pickCardsForLocation($nbr, 'deck_' . $phase, TOP_ROW);

        // Sort by type/cost
        usort($unsorted, array($this, 'compareCards'));

        // Update location_arg for board position
        $sorted = array();
        foreach ($unsorted as $i => $card) {
            $loc = $start_idx + $i;
            if ($card['location_arg'] != $loc) {
                $this->cards->moveCard($card['id'], TOP_ROW, $loc);
            }
            $sorted[$loc] = $card['type_arg'];
        }

        return $sorted;
    }

    /*
     * Move all cards on the board as far right as possible and return the
     * total number of cards on the board.
     * Cards on the lower row go all the way to the end; those above to the
     * next open position left of any lower cards.
     */
    function shiftCardsRight()
    {
        $num_cards = 0;
        foreach (array(BOTTOM_ROW, TOP_ROW) as $row) {
            $board = $this->cards->getCardsInLocation($row, null, 'location_arg');
            if (count($board) == 0) {
                continue;
            }

            // Build associative array of old => new positions for client
            $shifted = array();
            foreach ($board as $card) {
                $loc = $card['location_arg'];
                $shifted[$loc] = $num_cards;
                $this->cards->moveCard($card['id'], $row, $num_cards);
                $num_cards++;
            }

            // Row constants are string but client needs integer
            $row_num = ($row == BOTTOM_ROW ? 1 : 0);

            self::notifyAllPlayers('shiftRight', "", array(
                'columns' => $shifted,
                'row' => $row_num
            ));
        }

        return $num_cards;
    }

    /*
     * Move all cards on the board from the upper row to the lower
     */
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

    /*
     * Remove from the game all cards on the board lower row
     */
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

    /*
     * Return details of the Observatory with the given card ID
     */
    function getObservatory($card_id)
    {
        // Check card against each observatory global
        for ($i=0; $i<2; $i++) {
            if ($card_id == self::getGameStateValue('observatory_' . $i . '_id')) {
                return array(
                    'id' => $i,
                    'used' => self::getGameStateValue('observatory_' . $i . '_used')
                );
            }
        }

        // Card not one of the two known Observatories
        throw new feException("Invalid Observatory ID");
    }

    /*
     * Return true if given player's hand is full and cannot hold more cards
     */
    function isHandFull($player_id)
    {
        $max_hand = 3;
        // +1 card in hand if player owns the Warehouse
        $warehouse = $this->cards->getCardsOfTypeInLocation(
            PHASE_BUILDING, CARD_WAREHOUSE, 'table', $player_id);
        if (count($warehouse) == 1) {
            $max_hand++;
        }
        // Can be greater if player used and then displaced the Warehouse
        return count($this->cards->getPlayerHand($player_id)) >= $max_hand;
    }

    /*
     * Return true if given player has at least one valid card to displace
     * with given (trading) card--does NOT consider cost.
     * Return all possible cards that could be displaced in the given
     * (reference) trades array--DOES consider cost and available rubles.
     */
    function getTrades($card, $cost, $player_id, &$trades)
    {
        $has_trade = false;
        $card_info = $this->getCardInfo($card);
        $cards = $this->cards->getCardsInLocation('table', $player_id);
        $rubles = self::dbGetRubles($player_id);

        // Compare each card on player table to the given trading card
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

            if (max($cost - $p_info['card_value'], 1) > $rubles) {
                continue; // Not enough value/rubles
            }

            $trades[] = $p_card['id'];
        }

        return $has_trade;
    }

    /*
     * Return true if given player has at least one valid card to displace
     * with given (trading) card--DOES consider cost and available rubles
     */
    function hasTrades($card, $cost, $player_id)
    {
        $trades = array();
        $this->getTrades($card, $cost, $player_id, $trades);
        return count($trades) > 0;
    }

    /*
     * Return info on possible moves the player can take for this specific card
     */
    function getPossibleMoves($player_id, $card, $rubles, $hand_full=true, $row=0)
    {
        $cost = $this->getCardCost($card['id'], $row);
        $can_buy = $cost <= $rubles;

        $is_trading = $this->isTrading($card);
        $has_trade = false;
        $trades = array();
        if ($is_trading) {
            $has_trade = $this->getTrades($card, $cost, $player_id, $trades);
            $can_buy = count($trades) > 0;
        }

        return array(
            'card_id' => $card['id'],
            'card_type' => $card['type_arg'],
            'card_name' => $this->getCardName($card),
            'can_add' => !$hand_full,
            'can_buy' => $can_buy,
            'cost' => $cost,
            'is_trading' => $is_trading,
            'has_trade' => $has_trade,
            'trades' => $trades
        );
    }

    /*
     * Return array of possible moves the player can take for
     * each card on board, in hand, and special actions
     */
    function getAllPossibleMoves($player_id)
    {
        $rubles = self::dbGetRubles($player_id);
        $hand_full = $this->isHandFull($player_id);
        $possible_moves = array(
            0 => array(),
            1 => array(),
            ROW_HAND => array(),
            ROW_OBSERVATORY => array()
        );

        // Cards on board
        $row = 0;
        foreach (array(TOP_ROW, BOTTOM_ROW) as $row_loc) {
            $board = $this->cards->getCardsInLocation($row_loc);
            foreach ($board as $card) {
                $col = $card['location_arg'];
                $possible_moves[$row][$col] = $this->getPossibleMoves(
                    $player_id, $card, $rubles, $hand_full, $row);
            }
            $row += 1;
        }

        // Cards in hand
        $cards = $this->cards->getPlayerHand($player_id);
        foreach ($cards as $card) {
            $possible_moves[ROW_HAND][$card['id']] = $this->getPossibleMoves(
                $player_id, $card, $rubles);
        }

        // Observatory
        $current_phase = self::getGameStateValue('current_phase') % 4;
        if ($this->phases[$current_phase] == PHASE_BUILDING) {
            $cards = $this->cards->getCardsOfTypeInLocation(
                PHASE_BUILDING, CARD_OBSERVATORY, 'table', $player_id);
            foreach ($cards as $card) {
                $obs = $this->getObservatory($card['id']);
                if (!$obs['used']) {
                    $possible_moves[ROW_OBSERVATORY][$card['id']] = array(
                        'can_add' => true // to trigger possible move styling
                    );
                }
            }
        }

        return $possible_moves;
    }

    /*
     * Return the card at given board row, col location
     */
    function getSelectedCard($row, $col)
    {
        // Get card from correct location
        if ($row == 0) {
            $loc = TOP_ROW;
        } else if ($row == 1) {
            $loc = BOTTOM_ROW;
        } else if ($row == ROW_OBSERVATORY) {
            $loc = 'obs_tmp';
            $col = self::getActivePlayerId();
        }
        $cards = $this->cards->getCardsInLocation($loc, $col);

        // Verify a card exists here
        if (count($cards) != 1)
            throw new feException("Impossible selection");

        return array_shift($cards);
    }

    /*
     * Verify that given trading card can displace selected card
     */
    function checkTrade($card, $disp_id, $player_id)
    {
        // Verify displaced card exists and owned by player
        $disp_card = $this->cards->getCard($disp_id);
        if ($disp_card == null ||
            $disp_card['location'] != 'table' ||
            $disp_card['location_arg'] != $player_id)
        {
            throw new feException("Impossible trade card");
        }

        // Verify cards are of correct type to trade
        $card_info = $this->getCardInfo($card);
        $disp_info = $this->getCardInfo($disp_card);
        if ($card_info['card_trade_type'] != $disp_info['card_type'] ||
            ($disp_info['card_type'] == PHASE_WORKER &&
                $card_info['card_worker_type'] != $disp_info['card_worker_type'] &&
                $disp_info['card_worker_type'] != WORKER_ALL))
        {
            throw new BgaUserException(self::_("Wrong type of card to displace"));
        }

        // Check if trading used Observatory
        if ($disp_card['type_arg'] == CARD_OBSERVATORY) {
            $obs = $this->getObservatory($disp_id);
            if ($obs['used']) {
                throw new BgaUserException(self::_("You cannot displace an Observatory after using it"));
            }
        }
    }

    /*
     * Return true if player has a potential (though not necessarily valid)
     * move to make, and false otherwise.
     */
    function canPlay($player_id)
    {
        // Count cards available to play
        $hand = count($this->cards->getPlayerHand($player_id));
        $board = count($this->cards->getCardsInLocation(TOP_ROW));
        $board += count($this->cards->getCardsInLocation(BOTTOM_ROW));

        // If both options are set there is no private info in the game,
        // then autopass can be more aggressive and validate every play
        if ($this->optShowHands() && $this->optShowRubles()) {
            // Function used for player turn highlights all possible moves
            // Can play if any card available to buy or add
            // This also covers any usable Observatory
            $moves = $this->getAllPossibleMoves($player_id);
            foreach ($moves as $cards) {
                foreach ($cards as $card) {
                    if ($card['can_buy'] || $card['can_add']) {
                        return true;
                    }
                }
            }

            // No legal move
            return false;
        }

        // Money is secret, but it would always be known if a player has none
        $rubles = self::dbGetRubles($player_id);

        // Can play if any card is available with any money to spend
        if ($rubles > 0 && ($board + $hand) > 0) {
            // Do not check against card costs to protect secret information
            return true;
        }

        // Can play if any card is available to take in hand
        if ($rubles == 0 && $board > 0 && !$this->isHandFull($player_id)) {
            return true;
        }

        // Can play if Observatory can be used
        $current_phase = self::getGameStateValue('current_phase') % 4;
        if ($this->phases[$current_phase] == PHASE_BUILDING) {
            $cards = $this->cards->getCardsOfTypeInLocation(
                PHASE_BUILDING, CARD_OBSERVATORY, 'table', $player_id);
            foreach ($cards as $card) {
                $obs = $this->getObservatory($card['id']);
                if (!$obs['used']) {
                    return true;
                }
            }
        }

        // No play available
        return false;
    }

    /*
     * Return whether player is automatically passing turns
     */
    function dbGetAutoPass($player_id)
    {
        return $this->getUniqueValueFromDB("SELECT autopass FROM player WHERE player_id='$player_id'");
    }

    /*
     * Returns true if game option to show player hands is enabled
     */
    function optShowHands()
    {
        return $this->gamestate->table_globals[OPT_SHOW_HANDS] == 1;
    }

    /*
     * Returns true if game option to show player rubles is enabled
     */
    function optShowRubles()
    {
        return $this->gamestate->table_globals[OPT_SHOW_RUBLES] == 1;
    }
    
    function optEdition()
    {
        return $this->gamestate->table_globals[OPT_VERSION];
    }

    function opt2ndEdition()
    {
        return $this->optEdition() == 2;
    }
    
    //////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
     * Player adds a card to their hand
     */
    function addCard($card_row, $card_col)
    {
        self::checkAction('addCard');

        if ($this->opt2ndEdition() && self::getGameStateValue('current_phase') == 0) {
            throw new BgaUserException(self::_("You must buy on first worker phase"));
        }
        $card = $this->getSelectedCard($card_row, $card_col);

        // Verify player hand is not full
        if ($this->isHandFull(self::getActivePlayerId()))
            throw new BgaUserException(self::_("Your hand is full"));

        // Add to hand
        $dest = 'hand';
        $notif = 'addCard';
        $msg = clienttranslate('${player_name} adds ${card_name} to their hand');
        $this->cardAction($card['id'], -1, $card_row, 0, $dest, $notif, $msg);
        $this->gamestate->nextState('nextPlayer');
    }

    /*
     * Player buys a card
     */
    function buyCard($card_row, $card_col, $trade_id=-1)
    {
        self::checkAction('buyCard');

        $player_id = self::getActivePlayerId();
        $card = $this->getSelectedCard($card_row, $card_col);
        $card_id = $card['id'];

        // Verify trade if needed
        if ($this->isTrading($card)) {
            $this->checkTrade($card, $trade_id, $player_id);
        } else if ($trade_id > 0) {
            throw new feException("Impossible buy with trade");
        }

        // Verify player can pay cost
        $card_cost = $this->getCardCost($card_id, $card_row, $trade_id);
        $rubles = self::dbGetRubles($player_id);
        if ($card_cost > $rubles)
            throw new BgaUserException(self::_("You do not have enough rubles"));

        // Add card to player table
        $dest = 'table';
        $notif = 'buyCard';
        if ($trade_id > 0) {
            $msg = clienttranslate('${player_name} buys ${card_name}, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
            $msg = clienttranslate('${player_name} buys ${card_name} for ${card_cost} Ruble(s)');
        }
        $this->cardAction($card_id, $trade_id, $card_row, $card_cost, $dest, $notif, $msg);
        $this->gamestate->nextState('nextPlayer');
    }

    /*
     * Player play a card from their hand
     */
    function playCard($card_id, $trade_id=-1)
    {
        self::checkAction('playCard');

        $player_id = self::getActivePlayerId();
        $card = $this->cards->getCard($card_id);
        if ($card == null || $card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("Impossible play from hand");
        }

        // Verify trade if needed
        if ($this->isTrading($card)) {
            $this->checkTrade($card, $trade_id, $player_id);
        } else if ($trade_id > 0) {
            throw new feException("Impossible play with trade");
        }

        // Verify player can pay cost
        $card_cost = $this->getCardCost($card_id, 0, $trade_id);
        $rubles = self::dbGetRubles($player_id);
        if ($card_cost > $rubles)
            throw new BgaUserException(self::_("You do not have enough rubles"));

        // Add card to player table
        $dest = 'table';
        $notif = 'playCard';
        if ($trade_id > 0) {
            $msg = clienttranslate('${player_name} plays ${card_name} from their hand, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
            $msg = clienttranslate('${player_name} plays ${card_name} from their hand for ${card_cost} Ruble(s)');
        }
        $this->cardAction($card_id, $trade_id, 0, $card_cost, $dest, $notif, $msg);
        $this->gamestate->nextState('nextPlayer');
    }

    /*
     * Perform the appropriate action for the given card and destination
     *
     * Reduces duplication of code in main card actions (buy/add/play)
     */
    protected function cardAction($card_id, $trade_id, $card_row, $card_cost, $dest, $notif, $msg)
    {
        $card = $this->cards->getCard($card_id);
        $card_idx = $card['type_arg'];

        // Pay cost and take card
        $player_id = self::getActivePlayerId();
        $this->dbIncRubles($player_id, -$card_cost);
        $this->cards->moveCard($card_id, $dest, $player_id);

        // Stats
        self::incStat($card_cost, 'rubles_spent', $player_id);
        if ($dest == 'table') {
            self::incStat(1, 'cards_bought', $player_id);
            if ($trade_id > 0) {
                self::incStat(1, 'cards_traded', $player_id);
            }
        } else if ($dest == 'hand') {
            self::incStat(1, 'cards_added', $player_id);
        }

        if ($trade_id > 0) {
            // Discard displaced card
            $this->cards->playCard($trade_id);

            // Get info for log
            $trade = $this->cards->getCard($trade_id);
            $trade_name = $this->getCardName($trade);
        } else {
            $trade_name = '';
        }

        // Income
        if ($dest == 'table') {
            $income = $this->getIncome($player_id);
        } else {
            // No change to report
            $income = null;
        }

        self::notifyAllPlayers($notif, $msg, array(
            'i18n' => array('card_name', 'trade_name'),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->getCardName($card),
            'card_id' => $card_id,
            'card_idx' => $card_idx,
            'card_loc' => $card['location_arg'],
            'card_row' => $card_row,
            'card_cost' => $card_cost,
            'trade_id' => $trade_id,
            'trade_name' => $trade_name,
            'aristocrats' => $this->uniqueAristocrats($player_id),
            'income' => $income,
        ));

        // Reset globals
        self::setGameStateValue("activated_observatory", -1);
        self::setGameStateValue("num_pass", 0);
        self::incStat(1, 'actions_taken', $player_id);
    }

    /*
     * Player passes their turn
     */
    function pass()
    {
        self::checkAction('pass');

        if ($this->opt2ndEdition() && self::getGameStateValue('current_phase') == 0) {
            throw new BgaUserException(self::_("You must buy on first worker phase"));
        }
        $this->passActivePlayer('nextPlayer');
    }

    /*
     * Record pass action and check if all players have passed
     */
    function passActivePlayer($next_state)
    {
        // All players must pass in turn order to end current phase
        // Increment global pass counter to track when this happens
        $num_pass = self::incGameStateValue('num_pass', 1);
        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers('pass', clienttranslate('${player_name} passes'), array(
            'player_name' => self::getActivePlayerName(),
            'player_id' => $player_id,
            'state' => $next_state, // to track auto pass in debug
        ));

        if ($next_state != 'nextPlayer' && !$this->dbGetAutoPass($player_id)) {
            // Inform player they passed automatically
            $msg = clienttranslate('You cannot play and were forced to pass automatically');
            self::notifyPlayer($player_id, 'log', $msg, array());
        }

        // Determine if phase should end
        if ($num_pass == self::getPlayersNumber()) {
            // All players pass in turn => next phase
            // Reset pass counter for next phase
            self::setGameStateValue("num_pass", 0);
            $this->gamestate->nextState('allPass');
        } else {
            // One or more players left to pass => next player
            $this->gamestate->nextState($next_state);
        }
    }

    /*
     * Player chooses that (after the next action if active) they will
     * automatically pass all subsequent turns until the next phase
     */
    function enableAutoPass()
    {
        // No action check
        if ($this->opt2ndEdition() && self::getGameStateValue('current_phase') == 0) {
            throw new BgaUserException(self::_("You must buy on first worker phase"));
        }
        $player_id = self::getCurrentPlayerId(); // CURRENT: player can do this out of turn
        $this->DbQuery("UPDATE player SET autopass=1 WHERE player_id='$player_id'");
        self::notifyPlayer($player_id, 'autopass', '', array('enable' => true));

        // No state change, player continues to take normal action
    }

    /*
     * Player stops automatically passing their turns
     */
    function cancelAutoPass()
    {
        // No action check
        $player_id = self::getCurrentPlayerId(); // CURRENT: player can do this out of turn
        $this->DbQuery("UPDATE player SET autopass=0 WHERE player_id='$player_id'");
        self::notifyPlayer($player_id, 'autopass', '', array('enable' => false));
    }

    /*
     * Player buys points using a Pub
     */
    function buyPoints($points)
    {
        self::checkAction('buyPoints');

        // A player can buy up to 5 points for 2 rubles each with a Pub,
        // or up to 10 points if the player owns both
        $player_id = self::getCurrentPlayerId();
        $max_points = 0;
        $pubs = $this->cards->getCardsOfTypeInLocation(
            PHASE_BUILDING, CARD_PUB, 'table');
        foreach ($pubs as $card) {
            if ($card['location_arg'] == $player_id) {
                $max_points += 5;
            }
        }

        // Verify the number of points to buy and also that the current
        // player actually owns at least one Pub
        if ($points < 0 || $points > $max_points)
            throw new feException("Impossible pub buy");

        if ($points > 0) {
            // Verify player can pay the cost
            $rubles = self::dbGetRubles($player_id);
            $cost = $points * 2;
            if ($cost > $rubles)
                throw new BgaUserException(self::_("You do not have enough rubles"));

            // Update rubles, points, stats
            $this->dbIncRubles($player_id, -$cost);
            self::incStat($cost, 'rubles_spent', $player_id);
            $this->dbIncScore($player_id, $points);
            self::incStat($points, 'pub_points', $player_id);

            $msg = clienttranslate('${player_name} uses Pub to buy ${points} Point(s) for ${cost} Rubles');
            self::notifyAllPlayers('buyPoints', $msg, array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'points' => $points,
                'cost' => $cost
            ));
        } else {
            // No points, skip it
            self::notifyAllPlayers('message', clienttranslate('${player_name} declines to use the Pub bonus'), array(
                'player_name' => self::getCurrentPlayerName()
            ));
        }
        
        // Multiple active state as more than one player can own a Pub
        $this->gamestate->setPlayerNonMultiactive($player_id, 'nextPhase');
    }

    /*
     * Player uses Observatory to draw a card
     */
    function drawObservatoryCard($deck, $card_id)
    {
        self::checkAction('useObservatory');

        // Verify Observatory exists and owned by player
        $player_id = self::getActivePlayerId();
        $card = $this->cards->getCard($card_id);
        if ($card == null || $card['type_arg'] != CARD_OBSERVATORY ||
            $card['location_arg'] != $player_id || $card['location'] != 'table')
        {
            throw new feException("Invalid Observatory play");
        }

        // Verify Observatory is not already used and current phase is Building
        $obs = $this->getObservatory($card_id);
        $phase = self::getGameStateValue('current_phase') % 4;
        if ($obs['used'] || $this->phases[$phase] != PHASE_BUILDING)
            throw new BgaUserException(self::_("You cannot use the Observatory right now"));

        // Cannot draw from empty stack or take last card in stack
        $num_cards = $this->cards->countCardInLocation($deck);
        if ($num_cards == 0) {
            throw new BgaUserException(self::_("Card stack is empty"));
        } else if ($num_cards == 1) {
            throw new BgaUserException(self::_("You cannot draw the last card"));
        }

        // Draw card
        $card = $this->cards->pickCardForLocation($deck, 'obs_tmp', $player_id);
        if ($card == null || $this->cards->countCardInLocation('obs_tmp') != 1)
            throw new feException("Impossible Observatory draw");

        $phase = explode('_', $deck)[1];

        $msg = clienttranslate('Observatory: ${player_name} draws ${card_name} from the ${phase} stack');
        self::notifyAllPlayers('observatory', $msg, array(
            'i18n' => array('card_name', 'phase'),
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->getCardName($card),
            'phase' => $phase,
            'player_id' => $player_id,
        ));

        // Mark Observatrory as used
        self::setGameStateValue("activated_observatory", $obs['id']);
        self::setGameStateValue('observatory_' . $obs['id'] . '_used', 1);
        self::incStat(1, 'observatory_draws', $player_id);
        $this->gamestate->nextState("useObservatory");
    }
    
    /*
     * Player discards the card drawn with Observatory
     */
    function discardCard()
    {
        self::checkAction('discard');

        // Verify drawn card
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory discard");

        // Discard
        $card = array_shift($cards);
        $this->cards->playCard($card['id']);

        // Reuse end round discard notif arg
        $location = array();
        $location[] = array('row' => ROW_OBSERVATORY);

        $msg = clienttranslate('${player_name} discards ${card_name}');
        self::notifyAllPlayers('discard', $msg, array(
            'i18n' => array('card_name'),
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->getCardName($card),
            'cards' => $location
        ));

        // Reset card selection and pass counter globals
        self::setGameStateValue("activated_observatory", -1);
        self::setGameStateValue("num_pass", 0);
        self::incStat(1, 'actions_taken', $player_id);
        $this->gamestate->nextState('nextPlayer');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
     * Arguments for state: playerTurn
     * Main turn for player to select a card and add/buy/trade
     * Return all possible moves
     * 
     * N.B. pseudo private info included here (cards in hand)
     *      although the same info is available to any player
     *      paying attanetion and/or and keeping notes and is
     *      also recorded in the logs. TODO: fix it?
     */
    function argPlayerTurn()
    {
        $player_id = self::getActivePlayerId();
        return $this->getAllPossibleMoves($player_id);
    }

    /*
     * Arguments for state: useObservatory
     * Player draws a card with Observatory
     * Return card details and possible actions
     */
    function argUseObservatory()
    {
        // Get card drawn with Observatory
        $player_id = self::getActivePlayerId();
        $cards = $this->cards->getCardsInLocation('obs_tmp', $player_id);
        if ($cards == null || count($cards) != 1)
            throw new feException("Impossible Observatory recall");

        // Possible actions
        $card = array_shift($cards);
        $rubles = self::dbGetRubles($player_id);
        $hand_full = $this->isHandFull($player_id);
        $possible_moves = $this->getPossibleMoves($player_id, $card, $rubles, $hand_full);
        $obs_id = self::getGameStateValue("activated_observatory");
        $possible_moves['card'] = $card;
        $possible_moves['obs_id'] = self::getGameStateValue('observatory_' . $obs_id . '_id');
        $possible_moves['player_id'] = $player_id;
        $possible_moves['i18n'] = array('card_name');

        return $possible_moves;
    }

    /*
     * Arguments for state: usePub
     * Player(s) can choose to buy points with Pub
     * Return an array of player(s) that own one or more Pub cards where
     * key: player_id => value: maximum number of points they can buy
     * based on number or Pub cards (1 or 2) and available rubles
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

    /*
     * Game state: nextPlayer
     * Give more time and activate next player
     */
    function stNextPlayer()
    {
        // Next player
        $player_id = self::activeNextPlayer();

        // Count one turn when it gets back to the player that started this phase
        $current_phase = self::getGameStateValue('current_phase') % 4;
        $phase = $this->phases[$current_phase];
        $starting_player = self::getGameStateValue("starting_player_" . $phase);
        if ($player_id == $starting_player) {
            self::incStat(1, 'turns_number');
        }

        if ($this->dbGetAutoPass($player_id) || !$this->canPlay($player_id)) {
            // Player is auto passing or must pass since no available play
            $this->passActivePlayer('cantPlay');
        } else {
            // Next player turn
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextTurn');
        }
    }

    /*
     * Game state: scorePhase
     * Score end of phase and move to next phase or round, or end game
     */
    function stScorePhase()
    {
        // Get phase status
        $current_phase = self::getGameStateValue('current_phase') % 4;
        $new_round = ($current_phase == 3);

        // End game if last phase of final round just finished
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

    /*
     * Game state: usePub
     * Activate multiple player state for any player(s) owning Pub
     */
    function stUsePub()
    {
        $player_infos = self::loadPlayersBasicInfos();
        $player_id = null;
        $pub_players = array();

        // Allow any players that own a Pub to use it
        $pubs = $this->cards->getCardsOfTypeInLocation(PHASE_BUILDING, CARD_PUB, 'table');
        foreach ($pubs as $card) {
            if ($player_id == $card['location_arg']) {
                // Same player owns both
                break;
            }

            $player_id = $card['location_arg'];
            if ($this->dbGetRubles($player_id) > 0) {
                $pub_players[] = $player_id;
            } else {
                // Player has no money and must pass
                self::notifyAllPlayers('message', clienttranslate('${player_name} declines to use the Pub bonus'), array(
                    'player_name' => $player_infos[$player_id]['player_name']
                ));
                // Inform player they passed automatically
                $msg = clienttranslate('You cannot play and were forced to pass automatically');
                self::notifyPlayer($player_id, 'log', $msg, array());
            }
        }

        $this->gamestate->setPlayersMultiactive($pub_players, 'nextPhase', true);
    }

    /*
     * Game state: nextPhase
     * Progress from completed phase to the next phase/round and
     * track end game trigger
     */
    function stNextPhase()
    {
        // Increment phase
        $next_phase = self::incGameStateValue('current_phase', 1) % 4;
        $phase = $this->phases[$next_phase];

        // Clear any automatic passing
        $this->DbQuery("UPDATE player SET autopass=0");

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
            // and notify players to update score counters
            $obs_players = array();
            for ($i=0; $i<2; $i++) {
                if (self::getGameStateValue('observatory_' . $i . '_used') == 1) {
                    $card = $this->cards->getCard(self::getGameStateValue('observatory_' . $i . '_id'));
                    $obs_players[] = $card['location_arg'];
                    self::setGameStateValue('observatory_' . $i . '_used', 0);
                }
            }

            self::incStat(1, 'rounds_number');

            self::notifyAllPlayers('newRound', "", array(
                'tokens' => $tokens,
                'observatory' => $obs_players,
            ));
        }

        // Move all cards on board as far right as possible
        $num_cards = $this->shiftCardsRight();

        // Draw up to 8 new cards from current deck
        $new_cards = $this->drawCards(8 - $num_cards, $num_cards, $phase);

        // Check if deck was emptied to trigger final round
        if ($this->cards->countCardInLocation('deck_' . $phase) <= 0) {
            if (!self::getGameStateValue("last_round")) {
                self::setGameStateValue("last_round", 1);
                $msg = clienttranslate('Final round! ${phase} deck is empty');
                self::notifyAllPlayers('lastRound', $msg, array(
                    'i18n' => array('phase'),
                    'phase' => $phase
                ));
            }
        }

        // Activate starting player (_not_ next player) for next phase
        $starting_player = self::getGameStateValue("starting_player_" . $phase);
        $this->gamestate->changeActivePlayer($starting_player);
        self::incStat(1, 'turns_number');

        $msg = clienttranslate('${phase} phase begins, starting with ${player_name}');
        self::notifyAllPlayers('nextPhase', $msg, array(
            'i18n' => array('phase'),
            'player_name' => self::getActivePlayerName(),
            'phase' => $phase,
            'phase_arg' => $phase, // non-translated arg used in client (i18n came late)
            'cards' => $new_cards
        ));

        if ($this->canPlay($starting_player)) {
            self::giveExtraTime($starting_player);
            $this->gamestate->nextState('nextTurn');
        } else {
            // Player must pass since no available play
            $this->passActivePlayer('cantPlay');
        }
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
            } else if ($statename == "useObservatory") {
                // Clear Observatory selection
                self::setGameStateValue("activated_observatory", -1);

                // Discard drawn card
                $cards = $this->cards->getCardsInLocation('obs_tmp', $active_player);
                if ($cards != null && count($cards) == 1) {
                    $card = array_shift($cards);
                    $this->cards->playCard($card['id']);

                    // Notify client to clear UI for other players
                    $msg = clienttranslate('${card_name} is discarded automatically');
                    self::notifyAllPlayers('discard', $msg, array(
                        'i18n' => array('card_name'),
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
            $this->gamestate->setPlayerNonMultiactive($active_player, 'nextPhase');
            
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

        if ($from_version <= 2007120623) {
            $sql = "ALTER TABLE DBPREFIX_player ADD COLUMN `autopass` tinyint(1) unsigned NOT NULL DEFAULT 0";
            self::applyDbUpgradeToAllDB($sql);
        }

    }    
}
