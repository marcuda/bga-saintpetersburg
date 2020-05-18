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
  	
    public function selectCard()
    {
	self::setAjaxMode();
	$row = self::getArg("row", AT_posint, true);
	$col = self::getArg("col", AT_posint, true);
	$result = $this->game->selectCard($row, $col);
	self::ajaxResponse();
    }

    public function buyCard()
    {
	self::setAjaxMode();
	$result = $this->game->buyCard();
	self::ajaxResponse();
    }

    public function addCard()
    {
	self::setAjaxMode();
	$result = $this->game->addCard();
	self::ajaxResponse();
    }

    public function cancelSelect()
    {
	self::setAjaxMode();
	$result = $this->game->cancelSelect();
	self::ajaxResponse();
    }

    public function pass()
    {
	self::setAjaxMode();
	$result = $this->game->pass();
	self::ajaxResponse();
    }

    public function playCard()
    {
	self::setAjaxMode();
	$card_id = self::getArg("card_id", AT_posint, true);
	$result = $this->game->playCard($card_id);
	self::ajaxResponse();
    }

    public function tradeCard()
    {
	self::setAjaxMode();
	$card_id = self::getArg("card_id", AT_posint, true);
	$result = $this->game->tradeCard($card_id);
	self::ajaxResponse();
    }

    public function buyPoints()
    {
	self::setAjaxMode();
	$points = self::getArg("points", AT_posint, true);
	$result = $this->game->buyPoints($points);
	self::ajaxResponse();
    }

    public function useObservatory()
    {
	self::setAjaxMode();
	$card_id = self::getArg("card_id", AT_posint, true);
	$result = $this->game->useObservatory($card_id);
	self::ajaxResponse();
    }

    public function drawObservatoryCard()
    {
	self::setAjaxMode();
        $deck = self::getArg("deck", AT_alphanum, true);
	$result = $this->game->drawObservatoryCard($deck);
	self::ajaxResponse();
    }

    public function obsBuy()
    {
	self::setAjaxMode();
	$result = $this->game->obsBuy();
	self::ajaxResponse();
    }

    public function obsAdd()
    {
	self::setAjaxMode();
	$result = $this->game->obsAdd();
	self::ajaxResponse();
    }

    public function obsDiscard()
    {
	self::setAjaxMode();
	$result = $this->game->obsDiscard();
	self::ajaxResponse();
    }

}
  

