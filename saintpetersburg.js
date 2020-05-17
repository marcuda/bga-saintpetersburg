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
	    this.cardwidth = 70;
	    this.cardheight = 112;
	    this.cardwidth_big = 96;
	    this.cardheight_big = 150;
	    this.boardwidth = 8;
	    this.rubles = new ebg.counter();
	    this.player_tables = [];
	    this.player_hands = [];
	    this.phases = ['Worker', 'Building', 'Aristocrat', 'Trading'];
	    this.pub_points = 0;
	    this.max_pub_points = 0;
            this.current_phase = '';
            this.card_types = null;
            this.card_art_row_size = 10;
            this.deck_counters = [];
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

            this.card_types = gamedatas.card_types;
            
            // Setting up player boards
            for(var player_id in gamedatas.players) {
                var player = gamedatas.players[player_id];
		player.url = g_gamethemeurl;
		var player_board_div = $('player_board_' + player_id);
		dojo.place(this.format_block('jstpl_player_board', player), player_board_div);

		// Player cards
		var hand_counter = new ebg.counter();
		hand_counter.create('handcount_p' + player_id);
		hand_counter.setValue(gamedatas.player_hands[player_id]);
		this.player_hands[player_id] = hand_counter;
                this.addTooltip('handcount_p' + player_id, _("Number of cards in hand"), "");
                this.addTooltip('handcount_icon_p' + player_id, _("Number of cards in hand"), "");

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
                    this.addTooltip('rublecount_p' + player_id, _("Number of rubles"), "");
                    this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles"), "");
		} else {
                    this.addTooltip('rublecount_p' + player_id, _("Number of rubles (secret)"), "");
                    this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles (secret)"), "");
                }
            }

	    this.playerTable = this.player_tables[this.player_id];
            dojo.connect(this.playerTable, 'onChangeSelection', this, 'onPlayerTableSelectionChanged' );

	    this.setTokens(gamedatas.tokens, false);
	    this.setPhase(gamedatas.phase);
	    for (var deck in gamedatas.decks) {
		if (deck.startsWith('deck_')) {
                    dojo.connect($(deck), 'onclick', this, 'onClickDeck');
                    var phase = deck.split('_')[1];
                    this.deck_counters[phase] = new ebg.counter();
                    this.deck_counters[phase].create('count_' + phase);
                    this.deck_counters[phase].setValue(gamedatas.decks[deck]);
                    this.setDeckTooltip(phase, gamedatas.decks[deck]);
                }
	    }
            for (var i in this.phases) {
                var phase = this.phases[i];
                if (this.deck_counters[phase] == undefined) {
                    // No counter created means deck is empty
                    dojo.addClass('deck_' + phase, 'emptydeck')
                    dojo.style('count_' + phase, 'color', 'red');
                    this.setDeckTooltip(phase, 0);
                    // Counter shouldn't be needed but create it just in case
                    this.deck_counters[phase] = new ebg.counter();
                    this.deck_counters[phase].create('count_' + phase);
                    this.deck_counters[phase].setValue(0);
                }
            }

	    // Cards
	    // Hand
	    this.playerHand = this.createCardStock('myhand', 1);
	    this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
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

            // Observatory status
            for (var i in gamedatas.observatory) {
                var card = gamedatas.observatory[i];
                if (card.used == 1) {
                    dojo.style('card_content_active_' + card.id, 'display', 'none');
                    dojo.style('card_content_mask_' + card.id, 'display', 'block');
                }
            }

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
                    this.setSelections(args.args);
		    break;
		case 'tradeCard':
		    this.playerTable.setSelectionMode(1);
                    this.setSelections(args.args);
		    break;
                case 'tradeCardHand':
		    this.playerTable.setSelectionMode(1);
                    this.setSelections(args.args);
		    break;
                case 'useObservatory':
                    dojo.query('.deck').addClass('selectable');
                    break;
                case 'chooseObservatory':
                    this.showObservatoryChoice(args.args);
                    break;
                case 'tradeObservatory':
		    this.playerTable.setSelectionMode(1);
                    this.setSelections(args.args);
		    break;
                case 'usePub':
                    if (args.args[this.player_id] === undefined) {
                        // Should not get here...
                        this.max_pub_points = 0;
                    } else {
                        this.max_pub_points = args.args[this.player_id];
                    }
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
                case 'useObservatory':
                    dojo.query('.deck').removeClass('selectable');
                    break;
		case 'selectCard':
		case 'tradeCard':
                case 'tradeCardHand':
		case 'tradeObservatory':
                default:
                    this.playerHand.unselectAll();
		    this.playerTable.setSelectionMode(0);
                    dojo.query('.selected').removeClass('selected');
                    dojo.query('.selectable').removeClass('selectable');
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
                        var buy_color = args.can_buy ? "blue" : "gray";
                        var add_color = args.can_add ? "blue" : "gray";
			this.addActionButton("button_1", _("Buy ("+args.cost+")"), "onBuyCard", null, false, buy_color);
			this.addActionButton("button_2", _("Add to hand"), "onAddCard", null, false, add_color);
			this.addActionButton("button_3", _("Cancel"), "onCancelCard", null, false, "red");
			break;
		    case 'tradeCard':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
			break;
                    case 'tradeCardHand':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
			break;
		    case 'usePub':
                        var color = "blue";
                        if (args[this.player_id] == 0) {
                            color = "gray";
                        }
			this.addActionButton("button_1", "-1", "onOneLessPoint", null, false, "gray");
			this.addActionButton("button_2", "+1", "onOneMorePoint", null, false, color);
			this.addActionButton("button_3", _("Buy " + this.pub_points + " (" + this.pub_points * 2 + ")"), "onBuyPoints");
			this.addActionButton("button_4", _("Pass"), "onBuyNoPoints", null, false, "red");
			break;
                    case 'useObservatory':
			this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                        break;
                    case 'chooseObservatory':
                        var buy_color = args.can_buy ? "blue" : "gray";
                        var add_color = args.can_add ? "blue" : "gray";
			this.addActionButton("button_1", _("Buy ("+args.cost+")"), "onObsBuyCard", null, false, buy_color);
			this.addActionButton("button_2", _("Add to hand"), "onObsAddCard", null, false, add_color);
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
	    board.image_items_per_row = this.card_art_row_size;
	    for (var i = 0; i < 66; i++) {
		board.addItemType(i, i, g_gamethemeurl+'img/cards.jpg', i);
	    }
	    board.setSelectionMode(mode);
	    return board;
	},

	setupNewCard: function (card_div, card_type_id, card_id)
	{
            this.addTooltipHtml(card_div.id, this.getCardTooltip(card_type_id));

            if (card_type_id == 15 && card_div.id.substring(0, 6) != 'myhand') {
                // Observatory
                var player_id = parseInt(card_div.id.split('_')[1]);
                var id = card_id.split('_');
                id = id[id.length - 1];
                dojo.place(this.format_block('jstpl_card_content', {id:id}), card_div.id);
                if (player_id == this.player_id) {
                    // Active player
                    dojo.query('#card_content_active_' + id + '>a')[0].textContent = _("Activate");
                    dojo.connect(card_div, 'onclick', this, 'onClickObservatory');
                } else {
                    dojo.style('card_content_activewrap_' + id, 'display', 'none');
                }
            }
	},

        getCardTooltip: function (card_type_id)
        {
            var card = dojo.clone(this.card_types[card_type_id]);

            card.card_name = _(card.card_name);

	    card.artx = this.cardwidth_big * (card_type_id % this.card_art_row_size);
	    card.arty = this.cardheight_big * Math.floor(card_type_id / this.card_art_row_size);

            if (card.card_type == "Worker") {
                card.card_type = _("Worker") + " (" + _(card.card_worker_type) + ")";
            } else if (card.card_type == "Trading") {
                card.card_type = _(card.card_trade_type) + " (" + _("Trading card");
                if (card.card_trade_type == "Worker") {
                    card.card_type += " - " + _(card.card_worker_type);
                }
                card.card_type += ")";
            } else {
                card.card_type = _(card.card_type);
            }

            card.card_nbr_label = _("Cards in play");

            var txt = "<p>" + _("Cost") + ": " + card.card_cost + "</p>";
            if (card.card_rubles > 0) {
                txt += "<p>+" + card.card_rubles + " " + _("rubles") + "</p>";
            }
            if (card.card_points > 0) {
                txt += "<p>+" + card.card_points + " " + _("points") + "</p>";
            }

            if (typeof card.card_text != "undefined") {
                txt += "<p>" + _(card.card_text) + "</p>";
            }
            card.card_text = txt;

            return this.format_block("jstpl_card_tooltip", card);
        },

        resetTooltip: function (old_id, new_id)
        {
            // XXX 
            // This manipulates BGA Tooltip API rather than recreating it from scratch
            // Potentially an issue with furture API changes...
            if (this.tooltips[new_id]) {
                this.tooltips[new_id].destroy();
            }
            this.tooltips[new_id] = this.tooltips[old_id];
            this.tooltips[old_id] = null;
        },
            
	addCardOnBoard: function (row, col, idx, src='board')
	{
	    // Sprite index
	    var y = Math.trunc(idx / this.card_art_row_size);
	    var x = idx - (y * this.card_art_row_size);

	    x *= this.cardwidth
	    y *= this.cardheight

	    // Board position
	    col = 7 - col; // row of 8, first position far right

	    console.log('adding card type '+idx+' at x,y '+col+','+row);

            var card_div = 'card_' + col + '_' + row;

	    dojo.place(this.format_block('jstpl_card', {
		x:x,
		y:y,
		row: row,
		col: col
	    }), 'cards');

	    this.placeOnObject(card_div, src);
	    this.slideToObject(card_div, 'square_'+col+'_'+row).play();

            this.addTooltipHtml(card_div, this.getCardTooltip(idx));
	    dojo.connect($(card_div), 'onclick', this, 'onSelectCard');
	},

	setTokens: function (tokens, animate)
	{
            var delay = 1000; // 1s animation
            var players = {};

            // Clear tokens
            dojo.query('.token_small').removeClass('token_Worker token_Building token_Aristocrat token_Trading');

            for (var phase in tokens) {
                // Determine current and next player for each token
                var token = tokens[phase];
                if (players[token.next]) {
                    var curr = 'token2_p' + token.current;
                    var next = 'token2_p' + token.next;
                } else {
                    var curr = 'token_p' + token.current;
                    var next = 'token_p' + token.next;
                    players[token.next] = true;
                }

                if (animate) {
                    // Use temp object to show tokens rotating
                    var tmp = '<div id="tmp_token_'+phase+'" class="token_small token_'+phase+'"></div>';
                    this.slideTemporaryObject(tmp, 'token_wrap_p' + token.current, curr, next, delay, 0);
                } else {
                    // Immediately switch token to next player
                    dojo.addClass(next, 'token_' + phase);
                    this.addTooltip(next, _("Starting player for " + phase + " phase"), "");
                }
            }

            if (animate) {
                // Call this function again without animation to set permenant token icons
                // A bit overly complicated but animation callbacks were not working
                setTimeout(dojo.hitch(this, function() {
                    this.setTokens(tokens, false);
                }), delay);
            }
	},

	setPhase: function (phase)
	{
            var prev_phase = this.current_phase;
            this.current_phase = phase;
            if (prev_phase != '') {
                this.setDeckTooltip(prev_phase, this.deck_counters[prev_phase].getValue());
            }
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

        setSelections: function (args)
        {
            if (args.player_id != this.player_id) {
                // Not active player
                return;
            }

            console.log(args);

            // Highlight selected card
            // In hand?
            var div = this.playerHand.getItemDivId(args.card_id);
            if (!$(div)) {
                // Not hand. Board?
		var col = 7 - args.col;
                div = 'card_' + col + '_' + args.row;
	    } else {
                // Select stock item, otherwise won't show
                this.playerHand.selectItem(args.card_id);
            }

            if (!$(div)) {
                // No, must be from Observatory pick
                div = 'card_99_99';
	    }
            if (!$(div)) {
                // How did we get here?
                alert("ERROR: Impossible selection");
                return;
            }

            console.log('PLAYER SELECT ' + args.col + ',' + args.row + ' => ' + div);

            dojo.addClass(div, 'selected');

            // Highlight trade options
            for (var i in args.trades) {
                div = this.player_tables[args.player_id].getItemDivId(args.trades[i]);
                dojo.addClass(div, 'selectable');
            }
        },

        showObservatoryChoice: function (args)
        {
	    // Sprite index
            var idx = args.card.type_arg;
	    var y = Math.trunc(idx / this.card_art_row_size);
	    var x = idx - (y * this.card_art_row_size);

	    x *= this.cardwidth
	    y *= this.cardheight

            var card_id = 'card_99_99';
            if ($(card_id)) {
                // Card already exists on board
                // Player must have cancelled last action
                dojo.addClass(card_id, 'selected');
                return;
            }

            // Disable Observatory
            dojo.style('card_content_mask_' + args.obs_id, 'display', 'block');
            dojo.style('card_content_active_' + args.obs_id, 'display', 'none');

	    // Deck selection
            var num_cards = this.deck_counters[args.card.type].incValue(-1);
            this.setDeckTooltip(args.card.type, num_cards);

	    dojo.place(this.format_block('jstpl_card', {
		x:x,
		y:y,
		row: 99,
		col: 99
	    }), 'cards');

	    this.placeOnObject(card_id, 'deck_' + args.card.type);
            dojo.addClass(card_id, 'selected');
	    this.slideToObject(card_id, 'board').play();
            this.addTooltipHtml(card_id, this.getCardTooltip(args.card.type_arg));
        },

        setDeckTooltip: function (phase, cards)
        {
            var txt = "";
            if (phase == this.current_phase) {
                txt += "<b>Current phase:</b> ";
            }

            txt += phase + " stack ";

            if (cards == 0) {
                txt += "is empty meaning game will end soon";
            } else {
                txt += "has " + cards + " cards";
            }

            this.addTooltip('deck_' + phase, _(txt), "");
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

            this.pub_points -= 1;

            if (this.pub_points < 0) {
                this.showMessage(_("You cannot buy fewer than zero"), "error");
                this.pub_points = 0;
            }

            if (this.pub_points == 0) {
                // "Disable" -1 button
                dojo.removeClass('button_1', 'bgabutton_blue');
                dojo.addClass('button_1', 'bgabutton_gray');
            }

            if (this.pub_points < this.max_pub_points) {
                // "Enable" +1 button
                dojo.removeClass('button_2', 'bgabutton_gray');
                dojo.addClass('button_2', 'bgabutton_blue');
            }

	    $('button_3').textContent = _("Buy " + this.pub_points + " (" + this.pub_points * 2 + ")");
	},

	onOneMorePoint: function (evt)
	{
	    dojo.stopEvent(evt);

            this.pub_points += 1;

            if (this.pub_points > this.max_pub_points) {
                this.showMessage(_("You cannot buy any more points"), "error");
                this.pub_points = this.max_pub_points;
            }

            if (this.pub_points == this.max_pub_points) {
                // "Disable" +1 button
                dojo.removeClass('button_2', 'bgabutton_blue');
                dojo.addClass('button_2', 'bgabutton_gray');
            }

            if (this.pub_points > 0) {
                // "Enable" -1 button
                dojo.removeClass('button_1', 'bgabutton_gray');
                dojo.addClass('button_1', 'bgabutton_blue');
            }

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

            if (this.checkAction('tradeCard', true)) {
                // In trade state
                // Do not register click and let state machine handle the rest
                return;
            }

            if (!this.checkAction('useObservatory'))
                return;

            if (this.current_phase != this.phases[1]) {
                // Not building phase, can't use
                this.showMessage(_("You can only use the Observatory during the Building phase"), "error");
                return;
            }

	    var card_id = evt.currentTarget.id.split('_')[3];
            this.ajaxcall(
		"/saintpetersburg/saintpetersburg/useObservatory.html",
		{card_id: card_id}, this, function (result) {});
        },

        onClickDeck: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('drawObservatoryCard', true))
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
	    this.notifqueue.setSynchronous('shiftDown', 500);
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
		notif.args.trade_id, 'discard_pile');

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
                    this.resetTooltip('card_'+old_col+'_'+row, 'card_'+new_col+'_'+row);
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
                this.resetTooltip('card_'+col+'_0', 'card_'+col+'_1');
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
            var deck = 'deck_' + notif.args.phase;

	    var draw = 0;
	    for (var i in notif.args.cards) {
		this.addCardOnBoard(0, i, notif.args.cards[i], deck);
		draw++;
	    }

            var num_cards = this.deck_counters[notif.args.phase].incValue(-draw);
            this.setDeckTooltip(notif.args.phase, num_cards);
            if (num_cards == 0) {
                dojo.addClass(deck, 'emptydeck')
                dojo.style('count_' + notif.args.phase, 'color', 'red');
            }
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
		var anim = this.slideToObject('card_'+col+'_'+row, 'discard_pile');
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

	    this.setTokens(notif.args.tokens, true);

            dojo.query('.maskcard').style('display', 'none');
            dojo.query('.activecard').style('display', 'block');
	},

	notif_buyPoints: function (notif)
	{
	    console.log('notif buy points');
	    console.log(notif);

            this.scoreCtrl[notif.args.player_id].incValue(notif.args.points);

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
