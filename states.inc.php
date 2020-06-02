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
 * states.inc.php
 *
 * SaintPetersburg game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
// State constants
if (!defined("STATE_END_GAME")) {
    define("STATE_PLAYER_TURN", 10);
    define("STATE_TRADE_CARD",  12);
    define("STATE_TRADE_CARD_HAND",  20);
    define("STATE_NEXT_PLAYER", 13);
    define("STATE_SCORE_PHASE", 14);
    define("STATE_NEXT_PHASE",  15);
    define("STATE_USE_PUB",     16);
    define("STATE_USE_OBSERVATORY", 18);
    define("STATE_OBSERVATORY_TRADE", 19);
    define("STATE_END_GAME", 99);
}

// TODO: Code check claims this is long and potentially overkill...
// Many of these state are duplicative and could potentially be reduced down
// by a factor with added logic in the game state and arg functions.
// Would require a rather large refactor to move much of it to the client side

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => STATE_PLAYER_TURN )
    ),
    
    // Normal player turn
    STATE_PLAYER_TURN => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must choose a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must choose a card or pass'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => array("selectCard", "addCard", "buyCard", "playCard", "useObservatory", "pass", "cancel"),
        "transitions" => array(
            "addCard" => STATE_NEXT_PLAYER,
            "buyCard" => STATE_NEXT_PLAYER,
            "playCard" => STATE_NEXT_PLAYER,
            "tradeCard" => STATE_TRADE_CARD,
            "tradeCardHand" => STATE_TRADE_CARD_HAND,
            "drawCard" => STATE_USE_OBSERVATORY,
            "pass" => STATE_NEXT_PLAYER,
            "allPass" => STATE_SCORE_PHASE,
            "zombiePass" => STATE_NEXT_PLAYER,
            "zombieAllPass" => STATE_SCORE_PHASE
        )
    ),

    // Player wants to buy/play a trading card and needs to select one to displace
    STATE_TRADE_CARD => array(
        "name" => "tradeCard",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to displace'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to displace (base cost: ${cost})'),
        "type" => "activeplayer",
        "args" => "argTradeCard",
        "possibleactions" => array("tradeCard", "cancel"),
        "transitions" => array(
            "tradeCard" => STATE_NEXT_PLAYER,
            "cancel" => STATE_PLAYER_TURN,
            "zombiePass" => STATE_NEXT_PLAYER,
            "zombieAllPass" => STATE_SCORE_PHASE
        )
    ),

    /*
     * Player selects a Trading Card in hand. Cannot use the normal state
     * as it will reveal the card name in the active player's hand.
     *
     * N.B. Reusing the same args function technically still leaks the info;
     * however, it is also available in the game log so it can't really be
     * protected and is therefore not a concern here.
     */
    STATE_TRADE_CARD_HAND => array(
        "name" => "tradeCardHand",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to displace (base cost: ${cost})'),
        "type" => "activeplayer",
        "args" => "argTradeCard",
        "possibleactions" => array("tradeCard", "cancel"),
        "transitions" => array(
            "tradeCard" => STATE_NEXT_PLAYER,
            "cancel" => STATE_PLAYER_TURN,
            "zombiePass" => STATE_NEXT_PLAYER,
            "zombieAllPass" => STATE_SCORE_PHASE
        )
    ),

    // Game state to move to next player
    STATE_NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => false,
        "transitions" => array(
            "nextTurn" => STATE_PLAYER_TURN,
            "cantPlay" => STATE_NEXT_PLAYER
        )
    ),

    // Game state for end of phase scoring
    STATE_SCORE_PHASE => array(
        "name" => "scorePhase",
        "type" => "game",
        "action" => "stScorePhase",
        "updateGameProgression" => false,
        "transitions" => array(
            "nextPhase" => STATE_NEXT_PHASE,
            "usePub" => STATE_USE_PUB,
            "endGame" => STATE_END_GAME
        )
    ),

    // Player draws a card with Observatory and needs to choose what to do
    STATE_USE_OBSERVATORY => array(
        "name" => "useObservatory",
        "description" => clienttranslate('Observatory: ${actplayer} must take or discard'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must take or discard'),
        "type" => "activeplayer",
        "args" => "argUseObservatory",
        "possibleactions" => array("buyCard", "addCard", "discard"),
        "transitions" => array(
            "buyCard" => STATE_NEXT_PLAYER,
            "addCard" => STATE_NEXT_PLAYER,
            "discard" => STATE_NEXT_PLAYER,
            "zombiePass" => STATE_NEXT_PLAYER,
            "zombieAllPass" => STATE_SCORE_PHASE
        )
    ),

    // Player draws a trading card with Observatory and needs to select a card to displace
    STATE_OBSERVATORY_TRADE => array(
        "name" => "tradeObservatory",
        "description" => clienttranslate('${card_name}: ${actplayer} must choose a card to displace'),
        "descriptionmyturn" => clienttranslate('${card_name}: ${you} must choose a card to displace (base cost: ${cost})'),
        "type" => "activeplayer",
        "args" => "argTradeCard",
        "possibleactions" => array("tradeCard", "cancel"),
        "transitions" => array(
            "tradeCard" => STATE_NEXT_PLAYER,
            "cancel" => STATE_USE_OBSERVATORY,
            "zombiePass" => STATE_NEXT_PLAYER,
            "zombieAllPass" => STATE_SCORE_PHASE
        )
    ),

    // Player can use Pub to buy points
    STATE_USE_PUB => array(
        "name" => "usePub",
        "description" => clienttranslate('Other players may choose to use Pub'),
        "descriptionmyturn" => clienttranslate('Pub: ${you} may buy points for 2 Rubles each'),
        "type" => "multipleactiveplayer",
        "action" => "stUsePub",
        "args" => "argUsePub",
        "possibleactions" => array("buyPoints"),
        "transitions" => array(
            "nextPhase" => STATE_NEXT_PHASE
        )
    ),

    // Game state to progress to next phase
    STATE_NEXT_PHASE => array(
        "name" => "nextPhase",
        "type" => "game",
        "action" => "stNextPhase",
        "updateGameProgression" => true,
        "transitions" => array(
            "nextTurn" => STATE_PLAYER_TURN
        )
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


