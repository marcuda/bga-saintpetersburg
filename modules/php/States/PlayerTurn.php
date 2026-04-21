<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburg\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\Actions\Types\StringParam;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

/**
 * This active player state ask a player to play one turn.
 */
class PlayerTurn extends GameState
{

    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::PLAYER_TURN->value, type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must choose a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must choose a card or pass'));
    }

    /**
     * Get the state arguments to be sent to client.
     * Main turn for player to select a card and add/buy/trade.
     * Return all possible moves
     *
     * N.B. pseudo private info included here (cards in hand)
     * although the same info is available to any player
     * paying attention and/or and keeping notes and is
     * also recorded in the logs. TODO: fix it?
     *
     * @param int $activePlayerId The active player id.
     * @return array All possible moves.
     */
    public function getArgs(int $activePlayerId): array
    {
        return $this->game->getAllPossibleMoves($activePlayerId);
    }

    /**
     * Player adds a card to their hand.
     * @param int $row The row of the selected card.
     * @param int $col The column of the selected card.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actAddCard(#[IntParam(min: 0, max: 1)] int $row, #[IntParam(min: 0, max: 7)] int $col, int $activePlayerId)
    {
        $game = $this->game;
        if ($game->opt2ndEdition() && $game->getGameStateValue('current_phase') == 0) {
            throw new UserException(clienttranslate("You must buy on first worker phase"));
        }
        return $game->addCard($row, $col, $activePlayerId);
    }

    /**
     * Player buys a card.
     * @param int $row The row of the selected card.
     * @param int $col The column of the selected card.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actBuyCard(#[IntParam(min: 0, max: 1)] int $row, #[IntParam(min: 0, max: 7)] int $col, int $activePlayerId, int $trade_id = - 1)
    {
        return $this->game->buyCard($row, $col, $activePlayerId, $trade_id);
    }

    /**
     * Player plays a card from their hand.
     * @param int $card_id The card id.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return mixed The next state (NextPlayer).
     */
    #[PossibleAction]
    function actPlayCard(int $card_id, int $activePlayerId, int $trade_id = - 1)
    {
        $game = $this->game;
        $card = $game->cards->getCard($card_id);
        if ($card == null || $card['location'] != 'hand' || $card['location_arg'] != $activePlayerId) {
            throw new SystemException("Impossible play from hand");
        }

        // Verify trade if needed
        if ($game->isTrading($card)) {
            $game->checkTrade($card, $trade_id, $activePlayerId);
        } else if ($trade_id > 0) {
            throw new SystemException("Impossible play with trade");
        }

        // Verify player can pay cost
        $card_cost = $game->getCardCost($card_id, 0, $trade_id);
        $rubles = $game->getRubles($activePlayerId);
        if ($card_cost > $rubles)
            throw new UserException(clienttranslate("You do not have enough rubles"));

        // Add card to player table
        $dest = 'table';
        $notif = 'playCard';
        if ($trade_id > 0) {
            $msg = clienttranslate(
                '${player_name} plays ${card_name} from their hand, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
            $msg = clienttranslate('${player_name} plays ${card_name} from their hand for ${card_cost} Ruble(s)');
        }
        $game->cardAction($card_id, $trade_id, 0, $card_cost, $dest, $notif, $msg);
        return NextPlayer::class;
    }

    /**
     * Player passes their turn.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer or ScorePhase).
     */
    #[PossibleAction]
    function actPass(int $activePlayerId)
    {
        $game = $this->game;
        if ($game->opt2ndEdition() && $game->getGameStateValue('current_phase') == 0) {
            throw new UserException(clienttranslate("You must buy on first worker phase"));
        }
        return $game->passPlayer($activePlayerId, true);
    }

    // Must match each deck.
    const DECKS = [
        'deck_Worker',
        'deck_Building',
        'deck_Aristocrat',
        'deck_Trading'
    ];

    /**
     * Player uses Observatory to draw a card.
     * @param string $deck The drawn deck.
     * @param int $card_id The observatory card id.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (UseObservatory).
     */
    #[PossibleAction]
    function actUseObservatory(#[StringParam(enum: self::DECKS)] string $deck, int $card_id, int $activePlayerId)
    {
        $game = $this->game;
        // Verify Observatory exists and owned by player
        $card = $game->cards->getCard($card_id);
        if ($card == null || $card['type_arg'] != CARD_OBSERVATORY || $card['location_arg'] != $activePlayerId ||
            $card['location'] != 'table') {
            throw new SystemException("Invalid Observatory play");
        }

        // Verify Observatory is not already used and current phase is Building
        $obs = $game->getObservatory($card_id);
        $phase = $game->getGameStateValue('current_phase') % 4;
        if ($obs['used'] || $game->phases[$phase] != PHASE_BUILDING) {
            throw new UserException(clienttranslate("You cannot use the Observatory right now"));
        }
        // Cannot draw from empty stack or take last card in stack
        $num_cards = $game->cards->countCardInLocation($deck);
        if ($num_cards == 0) {
            throw new UserException(clienttranslate("Card stack is empty"));
        } else if ($num_cards == 1) {
            throw new UserException(clienttranslate("You cannot draw the last card"));
        }

        // Draw card
        $card = $game->cards->pickCardForLocation($deck, 'obs_tmp', $activePlayerId);
        if ($card == null || $game->cards->countCardInLocation('obs_tmp') != 1) {
            throw new SystemException("Impossible Observatory draw");
        }
        $phase = explode('_', $deck)[1];

        $msg = clienttranslate('Observatory: ${player_name} draws ${card_name} from the ${phase} stack');
        $this->bga->notify->all('observatory', $msg,
            array(
                'i18n' => ['card_name', 'phase'],
                'player_name' => $game->getPlayerNameById($activePlayerId),
                'card_name' => $game->getCardName($card),
                'phase' => $phase,
                'player_id' => $activePlayerId
            ));

        // Mark Observatrory as used
        $game->setGameStateValue("activated_observatory", $obs['id']);
        $game->setGameStateValue('observatory_' . $obs['id'] . '_used', 1);
        $this->bga->playerStats->inc('observatory_draws', 1, $activePlayerId);
        return UseObservatory::class;
    }
    
    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     *
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`,
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId)
    {
        // Level 0 zombie: just pass.
        return $this->game->passPlayer($playerId, true);
    }
}

