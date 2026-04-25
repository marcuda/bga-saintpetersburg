<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * This is the main file for your game logic.
 *
 */
declare(strict_types=1);

namespace Bga\Games\SaintPetersburg;

use Bga\GameFramework\Components\Deck;
use Bga\GameFramework\UserException;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\Actions\CheckAction;
use Bga\Games\SaintPetersburg\States\NextPlayer;
use Bga\Games\SaintPetersburg\States\ScorePhase;
use Bga\Games\SaintPetersburg\States\PlayerTurn;

class Game extends \Bga\GameFramework\Table
{
    public Deck $cards;

    // Full stack sizes for progression.
    private array $deck_size = [
        Phase::Worker->name => 31,
        Phase::Building->name => 28,
        Phase::Aristocrat->name => 27,
        Phase::Trading->name => 30
    ];
    private array $card_infos;
    private array $card_infos2nd;
    
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        require 'material.inc.php';
    
        $this->initGameStateLabels(array(
            "starting_player_" . Phase::Worker->name => 10,     // player_id holding Worker token (stone)
            "starting_player_" . Phase::Building->name => 11,   // player_id holding Building token
            "starting_player_" . Phase::Aristocrat->name => 12, // player_id holding Aristocrat token
            "starting_player_" . Phase::Trading->name => 13,    // player_id holding Trading token
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
            "market" => OPT_MARKET,
            "banquet" => OPT_BANQUET,
            "company" => OPT_COMPANY,
            "assistants" => OPT_ASSISTANTS,
            "events" => OPT_EVENTS,
            "assignments" => OPT_ASSIGNMENTS,
            "obstacles" => OPT_OBSTACLES,
        ));

        $this->cards = $this->bga->deckFactory->createDeck('card');
    }
        
    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must set up the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {    
        // Set the colors of the players with HTML color code
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach($players as $player_id => $player)
        {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        $this->DbQuery($sql);
        $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        $this->reloadPlayersBasicInfos();
        
        // Player rubles is tiebreaker and so held in aux score
        foreach(array_keys($players) as $playerId)
        {
            // start with 25 rubles
            $this->bga->playerScoreAux->set($playerId, 25);
        }
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        $this->setGameStateInitialValue("num_pass", 0);
        $this->setGameStateInitialValue("current_phase", 0);
        $this->setGameStateInitialValue("last_round", 0);

        // Player order for each phase
        $starting_tokens = array();
        foreach (Phase::cases() as $phase) {
            $starting_tokens[] = "starting_player_" . $phase->name;
        }
        shuffle($starting_tokens);

        $player_ids = array_keys($players);
        $num_players = count($players);

        for ($i=0; $i<4; $i++) {
            $token = $starting_tokens[$i];
            $player_id = $player_ids[$i % $num_players];
            $this->setGameStateInitialValue($token, $player_id);
        }

        // Init game statistics
        //   Count first turn
        $this->bga->tableStats->init('turns_number', 1);
        //   Count first round
        $this->bga->tableStats->init('rounds_number', 1);
        $this->bga->playerStats->init("actions_taken", 0);
        $this->bga->playerStats->init("rubles_spent", 0);
        $this->bga->playerStats->init("rubles_total", 0);
        $this->bga->playerStats->init("rubles_Worker", 0);
        $this->bga->playerStats->init("rubles_Building", 0);
        $this->bga->playerStats->init("rubles_Aristocrat", 0);
        $this->bga->playerStats->init("points_total", 0);
        $this->bga->playerStats->init("points_Worker", 0);
        $this->bga->playerStats->init("points_Building", 0);
        $this->bga->playerStats->init("points_Aristocrat", 0);
        $this->bga->playerStats->init("cards_bought", 0);
        $this->bga->playerStats->init("cards_added", 0);
        $this->bga->playerStats->init("cards_traded", 0);
        $this->bga->playerStats->init("pub_points", 0);
        $this->bga->playerStats->init("observatory_draws", 0);
        $this->bga->playerStats->init("points_aristocrats_end", 0);
        $this->bga->playerStats->init("points_rubles_end", 0);
        $this->bga->playerStats->init("points_hand_end", 0);

        // Init cards and decks
        // Create all cards
        $cards = array();
        foreach ($this->getCardInfos() as $idx => $card) {
            $cards[] = array(
                'type' => $card['card_type']->name,
                'type_arg' => $idx,
                'nbr' => $card['card_nbr']
            );
        }
        $this->cards->createCards($cards);

        // Split into decks for each type
        foreach (Phase::cases() as $phase) {
            $cards = $this->cards->getCardsOfType($phase->name);
            $deck = 'deck_' . $phase->name;
            $this->cards->moveCards(array_column($cards, 'id'), $deck);
            $this->cards->shuffle($deck);
        }

        // Initialize globals to handle Observatory use
        $obs = $this->cards->getCardsOfType(Phase::Building->name, CARD_OBSERVATORY);
        $i = 0;
        foreach ($obs as $card) {
            $this->setGameStateInitialValue("observatory_" . $i . "_id", $card['id']);
            $this->setGameStateInitialValue("observatory_" . $i . "_used", 0);
            $i++;
        }
        $this->setGameStateInitialValue("activated_observatory", -1);

        // Starting draw based on number of players
        $this->drawCards($num_players * 2, 0, Phase::Worker);

        // Activate first player (which is in general a good idea :))
        $this->gamestate->changeActivePlayer($this->getGameStateValue("starting_player_" . Phase::Worker->name));

        /************ End of the game initialization *****/
        return PlayerTurn::class;
    }

    /*
        getAllDatas: 
        
        Gather all information about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $result['version'] = $this->optEdition();
        
        // !! We must only return information visible by this player!!
        $current_player_id = (int)$this->getCurrentPlayerId();
    
        // Get information about players
        $sql = "SELECT player_id id, player_score score FROM player";
        $result['players'] = $this->getCollectionFromDb($sql);
  
        // Get all cards on table and number in hand for each player
        $players = $this->loadPlayersBasicInfos();
        $tables = array();
        $hands = array();
        $hand_size = array();
        $hand_type = array();
        $aristocrats = array();
        $income = array();
        foreach (array_keys($players) as $player_id)
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
            $result['rubles'] = $this->getRublesAll();
        } else if ($this->isSpectator()) {
            // Spectator can see no one rubles as they are private.
            $result['rubles'] = [];
        } else {
            // Only own rubles visible
            $result['rubles'] = array(
                $current_player_id => $this->getRubles($current_player_id)
            );
        }

        // Current player automatically passing?
        $result['autopass'] = $this->dbGetAutoPass($current_player_id);

        // Number players already passed this turn
        $result['num_pass'] = $this->getGameStateValue('num_pass');
        // Player order to mark passing (needed for spectator when player order not set)
        $result['players_in_order'] = $this->getPlayersInOrder();

        // Get starting player tokens for each phase
        $tokens = array();
        foreach (Phase::cases() as $token_phase) {
            $phaseName = $token_phase->name;
            $token = "starting_player_" . $phaseName;
            $token_player = $this->getGameStateValue($token);
            // Client expects two elements here as same input is used for new round logic.
            // In this case we don't have (or need) that information so set both to current.
            $tokens[$phaseName] = array(
                'current' => $token_player,
                'next' => $token_player
            );
        }
        $result['tokens'] = $tokens;

        // Current phase
        $result['phase'] = Phase::fromRound((int)$this->getGameStateValue('current_phase'))->name;
        $result['last_round'] = $this->getGameStateValue("last_round") == 1;

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
                'id' => $this->getGameStateValue('observatory_' . $i . '_id'),
                'used' => $this->getGameStateValue('observatory_' . $i . '_used'),
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

        $result['buyOnly'] = $this->opt2ndEdition() && $this->getGameStateValue('current_phase') == 0;
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer between 0 (=the game just started) and
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
        foreach (Phase::cases() as $phase) {
            $deck = 'deck_' . $phase->name;
            if (key_exists($deck, $counts)) {
                $count = $counts[$deck];
                $percent = $count / $this->deck_size[$phase->name];
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
            $val += 3 * ($this->getGameStateValue('current_phase') % 4); // 3% each phase
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
     * custom games rules, play will always proceed in natural order.
     */
    function getPlayersInOrder(): array
    {
        $result = array();

        $players = $this->loadPlayersBasicInfos();
        $next_player = $this->getNextPlayerTable();
        $player_id = (int)$this->getCurrentPlayerId();

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
    
    /**
     * Get the rubles a player owns.
     * @param int $playerId A player id.
     * @return int The rubles owned by the player.
     */
    public function getRubles(int $playerId): int
    {
        // Rubles are stored in auxiliary score counter as it is the tiebreaker.
        return $this->bga->playerScoreAux->get($playerId);
    }
    
    /**
     * Get the rubles of each player.
     * @return array Rubles per player id.
     */
    private function getRublesAll(): array
    {
        return $this->bga->playerScoreAux->getAll();
    }

    /**
     * Increment the number of rubles of the given player by the given amount,
     * which can be negative. Does not notify anyone.
     * @param int $playerId The player id.
     * @param int $inc The delat to apply.
     * @return int The new rubles total of the player.
     */
    function incRubles(int $playerId, int $inc): int
    {
        return $this->bga->playerScoreAux->inc($playerId, $inc, null);
    }

    /*
     * Comparison function for sorting cards by model,
     * which will naturally sort by cost as well
     */
    function compareCards(array $a, array $b): int
    {
        $infoA = $this->getCardInfo($a);
        $infoB = $this->getCardInfo($b);
        return $infoB['card_model'] - $infoA['card_model'];
    }
    
    /*
     * Return additional card information.
     * This information is stored outside the deck in order to make
     * use of the standard Deck implementation and functions.
     */
    function getCardInfos(): array
    {
        if ($this->opt2ndEdition()) {
            return $this->card_infos2nd;
        }
        return $this->card_infos;
    }
    
    /*
     * Return additional card information for the given card.
     * This information is stored outside the deck in order to make
     * use of the standard Deck implementation and functions.
     */
    function getCardInfo(array $card): array
    {
        return $this->getCardInfos()[$card['type_arg']];
    }

    /*
     * Return additional card information for the given card ID.
     * See getCardInfo
     */
    function getCardInfoById(int $card_id): array
    {
        $card = $this->cards->getCard($card_id);
        return $this->getCardInfo($card);
    }

    /*
     * Return the written name of the given card.
     * Convenience function mainly used for notifications.
     */
    function getCardName(array $card): string
    {
        return $this->getCardInfo($card)['card_name'];
    }

    /*
     * Return the active player's adjusted cost for the given card ID
     * accounting for all possible reductions
     */
    function getCardCost(int $card_id, int $row, int $trade_id=-1): int
    {
        // Get card details
        $card = $this->cards->getCard($card_id);
        $card_info = $this->getCardInfo($card);
        $this->dump('$card_info', $card_info);
        $card_model = $card_info['card_model'];

        // -1 if taken from the lower row
        if ($row != 1) {
            // Other locations (e.g. hand, Observatory) give no discount
            $row = 0;
        }
        $cost = $card_info['card_cost'] - $row;

        $player_id = (int)$this->getActivePlayerId();
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
    function isCardType(array $card, Phase $type): bool
    {
        $card_info = $this->getCardInfo($card);
        $is_type = $card['type'] == $type->name;
        $is_trade_type = ($card['type'] == Phase::Trading->name && $card_info['card_trade_type'] == $type);
        return ($is_type || $is_trade_type);
    }

    // Convenience functions of isCardType with each type
    function isWorker(array $card): bool { return $this->isCardType($card, Phase::Worker); }
    function isBuilding(array $card): bool { return $this->isCardType($card, Phase::Building); }
    function isAristocrat(array $card): bool { return $this->isCardType($card, Phase::Aristocrat); }
    function isTrading(array $card): bool { return $card['type'] == Phase::Trading->name; }

    /*
     * Count the number of different aristocrats the given player owns
     */
    function uniqueAristocrats(int $player_id): int
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
    function computeScoring(int $player_id, Phase $phase): array
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
                    $obs = $this->getObservatory((int)$card['id']);
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
    function getIncome(int $player_id): array
    {
        $phases = array(Phase::Worker, Phase::Building, Phase::Aristocrat);
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
     * Draw and sort a given number of cards from the given phase stack
     * onto the top row of the board, starting at the given location
     */
    function drawCards(int $nbr, int $start_idx, Phase $phase): array
    {
        // Draw cards from phase stack
        $unsorted = $this->cards->pickCardsForLocation($nbr, 'deck_' . $phase->name, TOP_ROW);

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
     * Get details of the Observatory with the given card ID.
     * @param int $card_id A card id matching one of the observatory.
     * @return array An array with the observatory id on 'id' key and its used flag on 'used' key.
     */
    function getObservatory(int $card_id): array
    {
        // Check card against each observatory global
        for ($i=0; $i<2; $i++) {
            if ($card_id == $this->getGameStateValue('observatory_' . $i . '_id')) {
                return array(
                    'id' => $i,
                    'used' => $this->getGameStateValue('observatory_' . $i . '_used')
                );
            }
        }

        // Card not one of the two known Observatories
        throw new SystemException("Invalid Observatory ID");
    }

    /*
     * Return true if given player's hand is full and cannot hold more cards
     */
    function isHandFull(int $player_id): bool
    {
        $max_hand = 3;
        // +1 card in hand if player owns the Warehouse
        $warehouse = $this->cards->getCardsOfTypeInLocation(
            Phase::Building->name, CARD_WAREHOUSE, 'table', $player_id);
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
     * @param array $card A card of the trading cards deck.
     * @param int $cost The card cost.
     * @param int $player_id The player id.
     * @param array $trades An array of int to fill with the displacable cards’ ids for which the player can afford the displace
     * cost.
     * @return bool True if the player has at least one card that could be displaced (right type) even if not enough rubles.
     */
    function getTrades(array $card, int $cost, int $player_id, array &$trades): bool
    {
        $has_trade = false;
        $card_info = $this->getCardInfo($card);
        $cards = $this->cards->getCardsInLocation('table', $player_id);
        $rubles = $this->getRubles($player_id);

        // Compare each card on player table to the given trading card
        foreach ($cards as $p_card) {
            $p_info = $this->getCardInfo($p_card);
            if ($card_info['card_trade_type'] != $p_info['card_type']) {
                // Not correct trading type
                continue;
            }
            if ($card_info['card_trade_type'] == Phase::Worker &&
                $card_info['card_worker_type'] != $p_info['card_worker_type'] &&
                    $p_info['card_worker_type'] != WORKER_ALL)
            {
                // Not correct worker type.
                continue;
            }
            if ($p_card['type_arg'] == CARD_OBSERVATORY) {
                $obs = $this->getObservatory((int)$p_card['id']);
                if ($obs['used']) {
                    // Observatory card has been used.
                    continue;
                }
            }

            $has_trade = true; // At least one valid card, ignoring cost

            if (max($cost - $p_info['card_value'], 1) > $rubles) {
                // Not enough value/rubles.
                continue;
            }

            $trades[] = (int)$p_card['id'];
        }

        return $has_trade;
    }

    /*
     * Return info on possible moves the player can take for this specific card
     */
    function getPossibleMoves(int $player_id, array $card, int $rubles, bool $hand_full=true, int $row=0): array
    {
        $cost = $this->getCardCost((int)$card['id'], $row);
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

    /**
     * Get all the possible moves for a player for each card on board, in hand, and special actions.
     * @param int $player_id A player id.
     * @return array The possible moves as an array.
     */
    function getAllPossibleMoves(int $player_id): array
    {
        $rubles = $this->getRubles($player_id);
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
        $current_phase = Phase::fromRound((int)$this->getGameStateValue('current_phase'));
        if ($current_phase == Phase::Building) {
            $cards = $this->cards->getCardsOfTypeInLocation(
                Phase::Building->name, CARD_OBSERVATORY, 'table', $player_id);
            foreach ($cards as $card) {
                $obs = $this->getObservatory((int)$card['id']);
                if (!$obs['used']) {
                    $possible_moves[ROW_OBSERVATORY][$card['id']] = array(
                        // To trigger possible move styling.
                        'can_add' => true,
                        'can_buy' => false
                    );
                }
            }
        }

        return $possible_moves;
    }

    /**
     * Return true if player has a potential (though not necessarily valid)
     * move to make, and false otherwise.
     * @param int $player_id A player id.
     * @return bool True if the player may have a move to attempt.
     */
    function canPlay(int $player_id): bool
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
            return array_any($moves, fn($cards) => array_any($cards, fn($card) => $card['can_buy'] || $card['can_add']));

            // No legal move
        }

        // Money is secret, but it would always be known if a player has none
        $rubles = $this->getRubles($player_id);

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
        $current_phase = Phase::fromRound((int)$this->getGameStateValue('current_phase'));
        if ($current_phase == Phase::Building) {
            $cards = $this->cards->getCardsOfTypeInLocation(
                Phase::Building->name, CARD_OBSERVATORY, 'table', $player_id);
            foreach ($cards as $card) {
                $obs = $this->getObservatory((int)$card['id']);
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
    function dbGetAutoPass(int $player_id): mixed
    {
        return $this->getUniqueValueFromDB("SELECT autopass FROM player WHERE player_id='$player_id'");
    }

    /*
     * Returns true if game option to show player hands is enabled
     */
    function optShowHands(): bool
    {
        return $this->bga->tableOptions->get(OPT_SHOW_HANDS) == 1;
    }

    /*
     * Returns true if game option to show player rubles is enabled
     */
    function optShowRubles(): bool
    {
        return $this->bga->tableOptions->get(OPT_SHOW_RUBLES) == 1;
    }
    
    function optEdition(): int
    {
        $edition = $this->bga->tableOptions->get(OPT_VERSION);
        if ($edition === null) {
            return 1;
        }
        return $edition;
    }

    function opt2ndEdition(): bool
    {
        return $this->optEdition() == 2;
    }
    
    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions common to several states.
    //////////// 
    /**
     * Record pass action and check if all players have passed.
     * @param int $playerId The passing player id.
     * @param bool $canPlay True if the player could have played.
     * @return string The next state.
     */
    function passPlayer(int $playerId, bool $canPlay): string
    {
        // All players must pass in turn order to end current phase
        // Increment global pass counter to track when this happens
        $num_pass = $this->incGameStateValue('num_pass', 1);
        $this->bga->notify->all('pass', clienttranslate('${player_name} passes'),
            array(
                'player_name' => $this->getPlayerNameById($playerId),
                'player_id' => $playerId,
                'canPlay' => $canPlay // to track auto pass in debug
            ));
        
        if ((!$canPlay) && ! $this->dbGetAutoPass($playerId)) {
            // Inform player they passed automatically
            $msg = clienttranslate('You cannot play and were forced to pass automatically');
            $this->notify->player($playerId, 'log', $msg);
        }
        
        // Determine if phase should end
        if ($num_pass == $this->getPlayersNumber()) {
            // All players pass in turn => next phase
            // Reset pass counter for next phase
            $this->setGameStateValue("num_pass", 0);
            return ScorePhase::class;
        } else {
            // One or more players left to pass => next player.
            return NextPlayer::class;
        }
    }
    
    /*
     * Player chooses that they will automatically pass all subsequent turns until the next phase.<br>
     * No check action as it can be done at any time.
     * @param bool $pass If true pass immediately else wait for next activation of player.
     */
    #[CheckAction(false)]
    function actAutoPass(bool $pass): void
    {
        if ($this->opt2ndEdition() && $this->getGameStateValue('current_phase') == 0) {
            throw new UserException(clienttranslate("You must buy on first worker phase"));
        }
        // CURRENT: player can do this out of turn
        $player_id = (int)$this->getCurrentPlayerId();
        $this->DbQuery("UPDATE player SET autopass=1 WHERE player_id='$player_id'");
        $this->notify->player($player_id, 'autopass', '', array('enable' => true));
        if ($pass) {
            if ($player_id !=  (int)$this->getActivePlayerId()) {
                throw new SystemException('Player is not active');
            }
            $this->gamestate->jumpToState($this->passPlayer($player_id, true));
        }
    }

    /*
     * Player stops automatically passing their turns.<br>
     * No check action as it can be done at any time.
     */
    #[CheckAction(false)]
    function actCancelAutoPass(): void
    {
        // CURRENT: player can do this out of turn
        $player_id = (int)$this->getCurrentPlayerId();
        $this->DbQuery("UPDATE player SET autopass=0 WHERE player_id='$player_id'");
        $this->notify->player($player_id, 'autopass', '', array('enable' => false));
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
    
    function upgradeTableDb($from_version): void
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
//            $this->applyDbUpgradeToAllDB($sql);
//        }
//        if($from_version <= 1405061421)
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB($sql);
//        }
//        // Please add your future database scheme changes here
//
//

        if ($from_version <= 2007120623) {
            $sql = "ALTER TABLE DBPREFIX_player ADD COLUMN `autopass` tinyint(1) unsigned NOT NULL DEFAULT 0";
            $this->applyDbUpgradeToAllDB($sql);
        }

    }
}
