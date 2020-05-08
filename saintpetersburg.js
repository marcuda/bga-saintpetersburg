/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * saintpetersburg.js
 *
 * SaintPetersburg user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.saintpetersburg", ebg.core.gamegui, {
        constructor: function (){
            console.log('saintpetersburg constructor');
              
            // Here, you can init the global variables of your user interface
	    this.playerHand = null;
	    this.playerTable = null;
	    this.cardwidth = 96;
	    this.cardheight = 150;
	    this.boardwidth = 8;
	    this.rubles = new ebg.counter();
	    this.player_tables = [];
	    this.player_hands = [];
	    this.phases = ['Worker', 'Building', 'Aristocrat', 'Trading'];
	    this.pub_points = 0;
            this.current_phase = 'Worker';
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function (gamedatas)
        {
            console.log("Starting game setup");
            
            // Setting up player boards
            for(var player_id in gamedatas.players) {
                var player = gamedatas.players[player_id];
		var player_board_div = $('player_board_' + player_id);
		dojo.place(this.format_block('jstpl_player_board', player), player_board_div);

		// Player cards
		var hand_counter = new ebg.counter();
		hand_counter.create('handcount_p' + player_id);
		hand_counter.setValue(gamedatas.player_hands[player_id]);
		this.player_hands[player_id] = hand_counter;

		this.player_tables[player_id] = this.createCardStock('playertable_' + player_id, 0);
	        this.player_tables[player_id].onItemCreate = dojo.hitch(this, 'setupNewCard');
		for (var i in gamedatas.player_tables[player_id]) {
		    var card = gamedatas.player_tables[player_id][i];
		    this.player_tables[player_id].addToStockWithId(card.type_arg, card.id);
		}

		// Rubles (only known for current player)
		if (player_id == this.player_id) {
		    this.rubles.create('rublecount_p' + this.player_id);
		    this.rubles.setValue(gamedatas.rubles);
		}
            }

	    this.playerTable = this.player_tables[this.player_id];
            dojo.connect(this.playerTable, 'onChangeSelection', this, 'onPlayerTableSelectionChanged' );

	    this.setTokens(gamedatas.tokens);
	    this.setPhase(gamedatas.phase);
	    for (var deck in gamedatas.decks) {
		if (deck.startsWith('deck_')) {
		    $(deck).textContent = gamedatas.decks[deck];
                    dojo.connect($(deck), 'onclick', this, 'onDeckClicked');
		}
	    }

	    // Cards
	    // Hand
	    this.playerHand = this.createCardStock('myhand', 1);
	    for (var i in gamedatas.hand) {
		var card = gamedatas.hand[i];
		this.playerHand.addToStockWithId(card.type_arg, card.id);
	    }
            dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');
            
	    // Board
	    for (var i in gamedatas.board_top) {
		var card = gamedatas.board_top[i];
		this.addCardOnBoard(0, card.location_arg, card.type_arg);
	    }
	    for (var i in gamedatas.board_bottom) {
		var card = gamedatas.board_bottom[i];
		this.addCardOnBoard(1, card.location_arg, card.type_arg);
	    }

	    dojo.query('.square').connect('onclick', this, 'onSelectCard');

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log("Ending game setup");
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args)
        {
            console.log('Entering state: ' + stateName);
            
            switch(stateName)
            {
		case 'playerTurn':
		    break;
		case 'selectCard':
		    this.playerHand.setSelectionMode(0);
		    break;
		case 'tradeCard':
		    this.playerHand.setSelectionMode(0);
		    this.playerTable.setSelectionMode(1);
		    break;
                case 'useObservatory':
                    dojo.query('.deck').addClass('possibleMove');
                    break;
                case 'chooseObservatory':
                    this.showObservatoryChoice(args.args);
                    break;
                case 'tradeObservatory':
		    this.playerHand.setSelectionMode(0);
		    this.playerTable.setSelectionMode(1);
		    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName)
        {
            console.log('Leaving state: ' + stateName);
            
            switch(stateName)
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style('my_html_block_id', 'display', 'none');
                
                break;
           */
		case 'selectCard':
		    this.playerHand.setSelectionMode(1);
		    break;
		case 'tradeCard':
		    this.playerHand.setSelectionMode(1);
		    this.playerTable.setSelectionMode(0);
		    break;
                case 'useObservatory':
                    dojo.query('.deck').removeClass('possibleMove');
                    break;
		case 'tradeObservatory':
		    this.playerHand.setSelectionMode(1);
		    this.playerTable.setSelectionMode(0);
		    break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function (stateName, args)
        {
            console.log('onUpdateActionButtons: ' + stateName);
                      
            if(this.isCurrentPlayerActive())
            {            
                switch(stateName)
                {
		    case 'playerTurn':
			this.addActionButton("button_1", _("Pass"), "onPass");
			break;
		    case 'selectCard':
			this.addActionButton("button_1", _("Buy ("+args.cost+")"), "onBuyCard");
			this.addActionButton("button_2", _("Add to hand"), "onAddCard");
			this.addActionButton("button_3", _("Cancel"), "onCancelCard", null, false, "red");
			break;
		    case 'tradeCard':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
			break;
		    case 'usePub':
			this.addActionButton("button_1", "-1", "onOneLessPoint");
			this.addActionButton("button_2", "+1", "onOneMorePoint");
			this.addActionButton("button_3", _("Buy " + this.pub_points + " (" + this.pub_points * 2 + ")"), "onBuyPoints");
			this.addActionButton("button_4", _("Pass"), "onBuyNoPoints", null, false, "red");
			break;
                    case 'useObservatory':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                        break;
                    case 'chooseObservatory':
			this.addActionButton("button_1", _("Buy ("+args.cost+")"), "onObsBuyCard");
			this.addActionButton("button_2", _("Add to hand"), "onObsAddCard");
			this.addActionButton("button_3", _("Discard"), "onObsDiscardCard");
                        break;
                    case 'tradeObservatory':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
			break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

	createCardStock: function (elem, mode)
	{
	    var board = new ebg.stock();
	    board.create(this, $(elem), this.cardwidth, this.cardheight);
	    board.image_items_per_row = 11;
	    for (var i = 0; i < 66; i++) {
		board.addItemType(i, i, g_gamethemeurl+'img/cards.jpg', i);
	    }
	    board.setSelectionMode(mode);
	    return board;
	},

	setupNewCard: function (card_div, card_type_id, card_id)
	{
            if (card_type_id == 25) {
                // Observatory
                var player_id = parseInt(card_div.id.split('_')[1]);
                if (player_id == this.player_id) {
                    // Active player
                    var id = card_id.split('_');
                    id = id[id.length - 1];
                    dojo.place(this.format_block('jstpl_card_content', {id:id}), card_div.id);
                    dojo.connect($('card_content_' + id), 'onclick', this, 'onClickObservatory');
                }
            }
	},

	addCardOnBoard: function (row, col, idx, src='board')
	{
	    // Sprite index
	    var y = Math.trunc(idx / this.playerHand.image_items_per_row);
	    var x = idx - (y * this.playerHand.image_items_per_row);

	    x *= this.cardwidth
	    y *= this.cardheight

	    // Board position
	    col = 7 - col; // row of 8, first position far right

	    console.log('adding card type '+idx+' at x,y '+col+','+row);
	    dojo.place(this.format_block('jstpl_card', {
		x:x,
		y:y,
		row: row,
		col: col
	    }), 'cards');

	    this.placeOnObject('card_'+col+'_'+row, src);
	    this.slideToObject('card_'+col+'_'+row, 'square_'+col+'_'+row).play();
	},

	setTokens: function (tokens)
	{
	    dojo.query('.token').removeClass();
	    for (var phase in tokens) {
		var el = 'token_p' + tokens[phase];
		if (dojo.hasClass(el, 'token')) {
		    el = 'token2_p' + tokens[phase];
		}

		dojo.addClass(el, 'token');
		dojo.addClass(el, 'token_' + phase);
	    }
	},

	setPhase: function (phase)
	{
            this.current_phase = phase;
	    var transform;
	    dojo.forEach(
		['transform', 'WebkitTransform', 'msTransform',
		    'MozTransform', 'OTransform'],
		function (name) {
		    if (typeof dojo.body().style[name] != 'undefined') {
			transform = name;
		    }
		}
	    );

	    dojo.forEach(this.phases, function (name) {
		var node = dojo.byId('deck_' + name);
		var curve = [-90, -90]; // no-op

		if (name == phase) {
		    curve = [-90, 0];
		} else if (node.style[transform] != 'rotate(-90deg)') {
		    curve = [0, -90];
		}

		var anim = new dojo.Animation({
		    curve: curve,
		    onAnimate: function (v) {
			node.style[transform] = 'rotate(' + v + 'deg)';
		    }
		}).play();
	    });
	},

        showObservatoryChoice: function (args)
        {
	    // Sprite index
            var idx = args.card.type_arg;
	    var y = Math.trunc(idx / this.playerHand.image_items_per_row);
	    var x = idx - (y * this.playerHand.image_items_per_row);

	    x *= this.cardwidth
	    y *= this.cardheight

            var card_id = 'card_99_99';
            if ($(card_id)) {
                // Card already exists on board
                // Player must have cancelled last action
                return;
            }

            // TODO disable Observatory

	    // Deck selection
            var deck = $('deck_' + args.card.type);
	    var cards = parseInt(deck.textContent);
	    deck.textContent = cards - 1;

	    dojo.place(this.format_block('jstpl_card', {
		x:x,
		y:y,
		row: 99,
		col: 99
	    }), 'cards');

	    this.placeOnObject(card_id, deck.id);
            dojo.addClass(card_id, 'selected');
	    this.slideToObject(card_id, 'board').play();
        },

        ///////////////////////////////////////////////////
        //// Player's action

	onSelectCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('selectCard'))
		return;

	    var coords = evt.currentTarget.id.split('_');
	    var col = 7 - coords[1];
	    var row = coords[2];

	    console.log("Sending selectCard at " + row + ',' + col);

	    this.ajaxcall("/saintpetersburg/saintpetersburg/selectCard.html", {
		row:row,
		col:col
	    }, this, function (result){});
	},

	onAddCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('addCard'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/addCard.html",
		{}, this, function (result) {});
	},

	onBuyCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('buyCard'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/buyCard.html",
		{}, this, function (result) {});
	},

	onCancelCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('cancel'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/cancelSelect.html",
		{}, this, function (result) {});
	},

	onPass: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('pass'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/pass.html",
		{}, this, function (result) {});
	},

	onOneLessPoint: function (evt)
	{
	    dojo.stopEvent(evt);
	    this.pub_points = Math.max(0, this.pub_points - 1);
	    $('button_3').textContent = _("Buy " + this.pub_points + " (" + this.pub_points * 2 + ")");
	},

	onOneMorePoint: function (evt)
	{
	    dojo.stopEvent(evt);
	    this.pub_points = Math.min(5, this.pub_points + 1);
	    $('button_3').textContent = _("Buy " + this.pub_points + " (" + this.pub_points * 2 + ")");
	},

	onBuyPoints: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('buyPoints'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/buyPoints.html",
		{points:this.pub_points}, this, function (result) {});
	    this.pub_points = 0;
	},

	onBuyNoPoints: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('buyPoints'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/buyPoints.html",
		{points:0}, this, function (result) {});
	    this.pub_points = 0;
	},

        onPlayerHandSelectionChanged: function ()
        {
            var items = this.playerHand.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard')) {
                    // Can play a card
                    var card_id = items[0].id;
                    
                    this.ajaxcall(
			"/saintpetersburg/saintpetersburg/playCard.html",
			{card_id: card_id}, this, function (result) {});

                    this.playerHand.unselectAll();
                } else {
                    this.playerHand.unselectAll();
                }                
            }
        },
        
        onPlayerTableSelectionChanged: function ()
        {
            var items = this.playerTable.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('tradeCard')) {
                    // Can play a card
                    var card_id = items[0].id;
                    
                    this.ajaxcall(
			"/saintpetersburg/saintpetersburg/tradeCard.html",
			{card_id: card_id}, this, function (result) {});

                    this.playerTable.unselectAll();
                } else {
                    this.playerTable.unselectAll();
                }                
            }
        },

        onClickObservatory: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('useObservatory'))
                return;

            if (this.current_phase != this.phases[1])
                // Not building phase, can't use
                return;

	    var card_id = evt.currentTarget.id.split('_')[2];
            this.ajaxcall(
		"/saintpetersburg/saintpetersburg/useObservatory.html",
		{card_id: card_id}, this, function (result) {});
        },

        onDeckClicked: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('drawObservatoryCard'))
                return;

            var deck = evt.currentTarget.id;
            this.ajaxcall(
		"/saintpetersburg/saintpetersburg/drawObservatoryCard.html",
		{deck: deck}, this, function (result) {});
        },

	onObsAddCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('addCard'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/obsAdd.html",
		{}, this, function (result) {});
	},

	onObsBuyCard: function (evt)
	{
	    dojo.stopEvent(evt);
	    if (!this.checkAction('buyCard'))
		return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/obsBuy.html",
		{}, this, function (result) {});
	},

        onObsDiscardCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('discard'))
                return;

	    this.ajaxcall(
		"/saintpetersburg/saintpetersburg/obsDiscard.html",
		{}, this, function (result) {});
        },

        

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your saintpetersburg.game.php file.
        
        */
        setupNotifications: function ()
        {
            console.log('notifications subscriptions setup');
            
	    dojo.subscribe('buyCard', this, 'notif_buyCard');
	    dojo.subscribe('addCard', this, 'notif_addCard');
	    dojo.subscribe('playCard', this, 'notif_playCard');
	    dojo.subscribe('tradeCard', this, 'notif_tradeCard');
	    dojo.subscribe('shiftRight', this, 'notif_shiftRight');
	    this.notifqueue.setSynchronous('shiftRight', 1000);
	    dojo.subscribe('shiftDown', this, 'notif_shiftDown');
	    this.notifqueue.setSynchronous('shiftDown', 1000);
	    dojo.subscribe('discard', this, 'notif_discard');
	    this.notifqueue.setSynchronous('discard', 1000);
	    dojo.subscribe('scorePhase', this, 'notif_scorePhase');
	    dojo.subscribe('nextPhase', this, 'notif_nextPhase');
	    this.notifqueue.setSynchronous('nextPhase', 1000);
	    dojo.subscribe('newScores', this, 'notif_newScores');
	    dojo.subscribe('lastRound', this, 'notif_lastRound');
	    dojo.subscribe('newRound', this, 'notif_newRound');
	    dojo.subscribe('buyPoints', this, 'notif_buyPoints');
        },  
        
	notif_buyCard: function (notif)
	{
	    console.log('buy card notif');
            console.log(notif);

	    var row = notif.args.card_row;
	    var col = 7 - notif.args.card_loc;
            var src = 'square_' + col + '_' + row;

            if (row == 99) {
                // Observatory pick
                col = 99;
                src = 'board';
            }

	    dojo.destroy('card_' + col + '_' + row);
	    this.player_tables[notif.args.player_id].addToStockWithId(
		notif.args.card_idx, notif.args.card_id, src);
	    if (this.player_id == notif.args.player_id) {
		this.rubles.incValue(-notif.args.card_cost);
	    }
	},

	notif_addCard: function (notif)
	{
	    console.log('add card notif');
	    console.log(notif);

	    var row = notif.args.card_row;
	    var col = 7 - notif.args.card_loc;
            var src = 'square_' + col + '_' + row;

            if (row == 99) {
                // Observatory pick
                col = 99;
                src = 'board';
            }

	    if (this.player_id == notif.args.player_id) {
		dojo.destroy('card_' + col + '_' + row);
		this.playerHand.addToStockWithId(
		    notif.args.card_idx, notif.args.card_id, src);
	    } else {
		var anim = this.slideToObject('card_' + col + '_' + row,
		    'player_board_' + notif.args.player_id);
		dojo.connect(anim, 'onEnd', function (node) {
		    dojo.destroy(node);
		});
		anim.play();
	    }

	    this.player_hands[notif.args.player_id].incValue(1);
	},

	notif_playCard: function (notif)
	{
	    console.log('buy card notif');
	    console.log(notif);

	    if (notif.args.player_id == this.player_id) {
		this.playerTable.addToStockWithId(
		    notif.args.card_idx, notif.args.card_id,
		    'myhand_item_' + notif.args.card_id);
		this.playerHand.removeFromStockById(notif.args.card_id);

		// Active player sees total after paying cost
		this.rubles.incValue(-notif.args.card_cost);
	    } else {
		this.player_tables[notif.args.player_id].addToStockWithId(
		    notif.args.card_idx, notif.args.card_id,
		    'overall_player_board_' + notif.args.player_id);
	    }

	    this.player_hands[notif.args.player_id].incValue(-1);
	},

	notif_tradeCard: function (notif)
	{
	    console.log('notif trade card');
	    console.log(notif);

	    // Remove displaced card
	    this.player_tables[notif.args.player_id].removeFromStockById(
		notif.args.trade_id, 'board');

	    // Add trading card from correct place
	    var row = notif.args.card_row;
	    if (row < 0) {
		// Play from hand
		if (notif.args.player_id == this.player_id) {
		    // Active player
		    this.playerTable.addToStockWithId(
			notif.args.card_idx, notif.args.card_id,
			'myhand_item_' + notif.args.card_id);
		    this.playerHand.removeFromStockById(notif.args.card_id);
		} else {
		    // Others
		    this.player_tables[notif.args.player_id].addToStockWithId(
			notif.args.card_idx, notif.args.card_id,
			'overall_player_board_' + notif.args.player_id);
		}

		// Decrement hand counter
		this.player_hands[notif.args.player_id].incValue(-1);
	    } else if (row == 99) {
                // Observatory pick
		dojo.destroy('card_99_99');
		this.player_tables[notif.args.player_id].addToStockWithId(
		    notif.args.card_idx, notif.args.card_id, 'board');
	    } else {
		// Buy from board
		var col = 7 - notif.args.card_loc;
		dojo.destroy('card_' + col + '_' + row);
		this.player_tables[notif.args.player_id].addToStockWithId(
		    notif.args.card_idx, notif.args.card_id,
		    'square_' + col + '_' + row);
	    }

	    // Active player sees total after paying cost
	    if (this.player_id == notif.args.player_id) {
		this.rubles.incValue(-notif.args.card_cost);
	    }
	},

	notif_shiftRight: function (notif)
	{
	    console.log('notif shift right');
	    console.log(notif);

	    var row = notif.args.row;
	    for (var i in notif.args.columns) {
		var old_col = 7 - i;
		var new_col = 7 - notif.args.columns[i];
		if (new_col != old_col) {
		    // Slide card right to new position
		    this.slideToObject('card_'+old_col+'_'+row, 'square_'+new_col+'_'+row).play();
		    // Update card DOM id for new position
		    dojo.attr('card_'+old_col+'_'+row, 'id', 'card_'+new_col+'_'+row);
		}
	    }

	},

	notif_shiftDown: function (notif)
	{
	    console.log('notif shift down');
	    console.log(notif);

	    for (var i in notif.args.columns) {
		var col = 7 - notif.args.columns[i];
		// Slide card down to new position
		this.slideToObject('card_'+col+'_0', 'square_'+col+'_1').play();
		// Update card DOM id for new position
		dojo.attr('card_'+col+'_0', 'id', 'card_'+col+'_1');
	    }
	},

	notif_scorePhase: function (notif)
	{
	    console.log('notif score phase');
	    console.log(notif);

	    if (notif.args.player_id == this.player_id) {
		this.rubles.incValue(notif.args.rubles);
	    }
	},

	notif_nextPhase: function (notif)
	{
	    console.log('notif next phase');
	    console.log(notif);

	    this.setPhase(notif.args.phase);

	    var draw = 0;
	    for (var i in notif.args.cards) {
		this.addCardOnBoard(0, i, notif.args.cards[i], 'deck_' + notif.args.phase);
		draw++;
	    }

	    var deck = $('deck_' + notif.args.phase);
	    var cards = parseInt(deck.textContent);
	    deck.textContent = cards - draw;
	},

	notif_newScores: function (notif)
	{
	    console.log('notif new scores');
	    console.log(notif);

	    for(var player_id in notif.args.scores)
	    {
		var newScore = notif.args.scores[player_id];
		this.scoreCtrl[player_id].toValue(newScore);
	    }
	},

	notif_discard: function (notif)
	{
	    console.log('notif discard');
	    console.log(notif);

	    for (var i in notif.args.cards) {
		var card = notif.args.cards[i];
		var row = card.row;
                if (row == 99) {
                    // Observatory pick
                    var col = 99;
                } else {
                    // Reverse order
		    var col = 7 - card.col;
                }
		var anim = this.slideToObject('card_'+col+'_'+row, 'deck_Worker'); // TODO - discard pile
		dojo.connect(anim, 'onEnd', function (node) {
		    dojo.destroy(node);
		});
		anim.play();
	    }
	},

	notif_newRound: function (notif)
	{
	    console.log('notif new round');
	    console.log(notif);

	    this.setTokens(notif.args.tokens);

            //TODO reset observatory notif.args.obs_id1 and id2
	},

	notif_buyPoints: function (notif)
	{
	    console.log('notif buy points');
	    console.log(notif);

	    if (notif.args.player_id == this.player_id) {
		this.rubles.incValue(-notif.args.cost);
	    }
	},

	notif_lastRound: function (notif)
	{
	    this.showMessage(_('This is now the final round!'), 'info');
	},
        
   });             
});
