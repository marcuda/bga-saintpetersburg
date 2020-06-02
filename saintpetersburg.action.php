<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * saintpetersburg.action.php
 *
 * SaintPetersburg main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/saintpetersburg/saintpetersburg/myAction.html", ...)
 *
 */
  
  
class action_saintpetersburg extends APP_GameAction
{ 
    // Constructor: please do not modify
    public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
            $this->view = "saintpetersburg_saintpetersburg";
            self::trace( "Complete reinitialization of board game" );
        }
    } 
        
    // Player clicks the 'Buy' button
    public function buyCard()
    {
        self::setAjaxMode();
        $row = self::getArg("row", AT_posint, true);
        $col = self::getArg("col", AT_posint, true);
        $trade_id = self::getArg("trade_id", AT_posint, false, -1);
        $result = $this->game->buyCard($row, $col, $trade_id);
        self::ajaxResponse();
    }

    // Player clicks the 'Add to hand' button
    public function addCard()
    {
        self::setAjaxMode();
        $row = self::getArg("row", AT_posint, true);
        $col = self::getArg("col", AT_posint, true);
        $result = $this->game->addCard($row, $col);
        self::ajaxResponse();
    }

    // Player clicks the 'Pass' button
    public function pass()
    {
        self::setAjaxMode();
        $result = $this->game->pass();
        self::ajaxResponse();
    }

    // Player clicks a card in their hand
    public function playCard()
    {
        self::setAjaxMode();
        $card_id = self::getArg("col", AT_posint, true); // hand card id
        $trade_id = self::getArg("trade_id", AT_posint, false, -1);
        $result = $this->game->playCard($card_id, $trade_id);
        self::ajaxResponse();
    }

    // Player clicks the 'Buy' button for the Pub bonus
    public function buyPoints()
    {
        self::setAjaxMode();
        $points = self::getArg("points", AT_posint, true); // number points
        $result = $this->game->buyPoints($points);
        self::ajaxResponse();
    }

    // Player clicks a deck while using Observatory
    public function drawObservatoryCard()
    {
        self::setAjaxMode();
        $deck = self::getArg("deck", AT_alphanum, true); // deck name
        $obs_id = self::getArg("obs_id", AT_posint, true); // observastory id
        $result = $this->game->drawObservatoryCard($deck, $obs_id);
        self::ajaxResponse();
    }

    // Player clicks the 'Discard' button while using Observatory
    public function discardCard()
    {
        self::setAjaxMode();
        $result = $this->game->discardCard();
        self::ajaxResponse();
    }

}
