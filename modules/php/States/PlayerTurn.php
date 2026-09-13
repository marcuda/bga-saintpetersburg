<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburgExpansion\States;

use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\Actions\Types\StringParam;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburgExpansion\CardState;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\Phase;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This active player state ask a player to play one turn.
 */
class PlayerTurn extends CardState
{

    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::PLAYER_TURN,
            description: clienttranslate('${actplayer} must choose a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must choose a card or pass'));
    }

    /**
     * Get the state arguments to be sent to client.
     * Main turn for player to select a card and add/buy/trade.
     * Return all possible moves.
     *
     * @param int $activePlayerId The active player id.
     * @return array All possible moves.
     */
    public function getArgs(int $activePlayerId): array
    {
        return [
            '_private' => [
                $activePlayerId => [
                    'possibleMoves' => $this->game->getAllPossibleMoves($activePlayerId)
                ]
            ],
        ];
    }

    /**
     * Player adds a card to their hand.
     * @param int $row The row of the selected card.
     * @param int $col The column of the selected card.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer).
     * @throws SystemException When no card exist at given location.
     * @throws UserException When player hand is full or when player must buy a worker.
     */
    #[PossibleAction]
    function actAddCard(#[IntParam(min: 0, max: 1)] int $row, #[IntParam(min: 0, max: 9)] int $col, int $activePlayerId)
    {
        $game = $this->game;
        if ($game->opt2ndEdition() && $game->getGameStateValue('current_phase') == 0) {
            throw new UserException(clienttranslate("You must buy on first worker phase"));
        }
        return $this->addCard($row, $col, $activePlayerId);
    }

    /**
     * Player buys a card.
     * @param int $row The row of the selected card.
     * @param int $col The column of the selected card.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return mixed The next state (NextPlayer).
     * @throws SystemException When no card exist at given location or trade is not possible.
     * @throws UserException When player does not have enough rubles.
     */
    #[PossibleAction]
    function actBuyCard(#[IntParam(min: 0, max: 1)] int $row, #[IntParam(min: 0, max: 9)] int $col, int $activePlayerId, int $trade_id = - 1)
    {
        return $this->buyCard($row, $col, $activePlayerId, $trade_id);
    }

    /**
     * Player plays a card from their hand.
     * @param int $card_id The card id.
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or the discarded card id if playing away with it or the inflicted card id
     * if playing jester or banquet or -1.
     * @return mixed The next state (NextPlayer, BlackMarket, PlayerTurn).
     * @throws SystemException When card is not valid or can not be traded.
     * @throws UserException When player doe not have enough rubles.
     */
    #[PossibleAction]
    function actPlayCard(int $card_id, int $activePlayerId, int $trade_id = - 1)
    {
        $game = $this->game;
        $card = $game->cards->getCard($card_id);
        if ($card == null || $card['location'] != 'hand' || $card['location_arg'] != $activePlayerId) {
            throw new SystemException("Impossible play from hand");
        }

        $card_cost = $game->getCardCost($card_id, 0, $trade_id);
        $specialCard = $card_cost == 0;
        // Verify trade if needed
        if (!$specialCard) {
            if ($game->isTrading($card)) {
                $this->checkTrade($card, $trade_id, $activePlayerId);
            } else if ($trade_id > 0) {
                throw new SystemException("Impossible play with trade");
            }
        }

        // Verify player can pay cost
        $rubles = $game->getRubles($activePlayerId);
        if ($card_cost > $rubles) {
            throw new UserException(clienttranslate("You do not have enough rubles"));
        }

        if ($specialCard) {
            return $this->playSpecialCard($card, $activePlayerId, $trade_id);
        }
        // Add card to player table
        $dest = 'table';
        $notif = 'playCard';
        if ($trade_id > 0) {
            $msg = clienttranslate(
                '${player_name} plays ${card_name} from their hand, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
            $msg = clienttranslate('${player_name} plays ${card_name} from their hand for ${card_cost} Ruble(s)');
        }
        $this->cardAction($card_id, $trade_id, 0, $card_cost, $dest, $notif, $msg, $activePlayerId);
        return $game->getNextState();
    }

    private function playSpecialCard(array $card, int $activePlayerId, int $inflictedCardId): string
    {
        $game = $this->game;
        if ($inflictedCardId >= 0) {
            $inflictedCard = $game->cards->getCard($inflictedCardId);
            if ($inflictedCard == null || $inflictedCard['location_arg'] != $activePlayerId) {
                throw new SystemException("Impossible inflicted card");
            }
        } else {
            $inflictedCard = null;
        }
        switch ($card['type_arg']) {
            case CARD_AWAY_WITH_IT_BUILDING:
            case CARD_AWAY_WITH_IT_ARISTOCRAT:
                if ($inflictedCard == null || $inflictedCard['location'] != 'hand') {
                    throw new SystemException("Impossible discard from hand");
                }
                $this->cardAction((int)$card['id'], $inflictedCardId, 0, 0, 'discard', 'playCard', clienttranslate(
                    '${player_name} plays ${card_name} from their hand, discarding ${trade_name}'), $activePlayerId);
                return $game->getNextState();
            case CARD_BLACK_MARKET:
                if ($game->cards->countCardsInLocation('discard') == 0) {
                    throw new SystemException("Black market can not be played now");
                }
                $this->cardAction((int)$card['id'], $inflictedCardId, 0, 0, 'beingPlayed', 'playCard', clienttranslate(
                    '${player_name} plays ${card_name} from their hand'), $activePlayerId);
                return BlackMarket::class;
            case CARD_GOLDEN_DONKEY:
                $game->incRubles($activePlayerId, 5);
                $this->bga->playerStats->inc('rubles_total', 5, $activePlayerId);
                $this->cardAction((int)$card['id'], $inflictedCardId, 0, 0, 'discard', 'playCard', clienttranslate(
                    '${player_name} plays ${card_name} from their hand, and gains 5 rubles'), $activePlayerId);
                return $game->getNextState();
            case CARD_DOUBLE_TURN:
                $this->cardAction((int)$card['id'], $inflictedCardId, 0, 0, 'discard', 'playCard', clienttranslate(
                    '${player_name} plays ${card_name} from their hand, and has two more turns to play'), $activePlayerId);
                $game->setGameStateValue("doubleTurn", 1);
                return PlayerTurn::class;
            case CARD_PICKPOCKET:
                throw new SystemException("Pickpocket can not be played now");
            case CARD_JESTER:
                return $this->applyCardToInflicted($card, $activePlayerId, $inflictedCard, 'jesterCard');
            case CARD_BANQUET:
                return $this->applyCardToInflicted($card, $activePlayerId, $inflictedCard, 'banquetCard');
            default:
                throw new SystemException("Unexpected special card: " . $card['type_arg']);
        }
    }

    private function applyCardToInflicted(array $card, int $activePlayerId, array $inflictedCard, string $gameState): string
    {
        if ($inflictedCard == null || $inflictedCard['location'] != 'table') {
            throw new SystemException("Special card can not be applied to this card");
        }
        $game = $this->game;
        $inflictedCardInfo = $game->getCardInfo($inflictedCard);
        if ($inflictedCardInfo['card_rubles'] == 0 || $inflictedCardInfo['card_points'] == 0) {
            throw new SystemException("Special card can not be applied to this card");
        }
        $game->setGameStateValue($gameState, (int)$inflictedCard['id']);
        $this->cardAction((int)$card['id'], (int)$inflictedCard['id'], 0, 0, 'table', 'playCard', clienttranslate(
            '${player_name} plays ${card_name} from their hand, applying it to ${trade_name}'), $activePlayerId);
        return $game->getNextState();
    }

    /**
     * Player use debtor’s prison.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (UsePrison).
     * @throws SystemException If not in building phase or prison already used or no card in discard pile.
     */
    #[PossibleAction]
    function actUsePrison(int $activePlayerId)
    {
        $game = $this->game;
        if (Phase::fromRound((int)$game->getGameStateValue('current_phase')) != Phase::Building) {
            throw new SystemException('Debtor’s prison must be used in building phase.');
        }
        if ($game->getGameStateValue('debtors_prison_used') != 0) {
            throw new SystemException('Debtor’s prison already used.');
        }
        // Check at least on card in discard.
        if ($game->cards->countCardInLocation('discard') < 1) {
            throw new SystemException('Attempt to use prison without any discarded card.');
        }

        $game->setGameStateValue('debtors_prison_used', 1);
        $this->bga->playerStats->inc('prisonPicks', 1, $activePlayerId);
        return UsePrison::class;
    }

    /**
     * Player passes their turn.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer or ScorePhase).
     * @throws UserException When player must buy a worker.
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
    const array DECKS = [
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
     * @throws SystemException When the given card id does not match a valid card or no card can be drawn from given
     * deck.
     * @throws UserException When the observatory can not be used.
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
        $phase = Phase::fromRound((int)$game->getGameStateValue('current_phase'));
        if ($obs['used'] || $phase != Phase::Building) {
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

        // Mark observatory as used
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

