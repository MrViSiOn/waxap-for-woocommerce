/* global waxapInbox, jQuery */
( function ( $ ) {
	'use strict';

	var WaxapInboxApp = {
		currentPhone: null,
		pollTimer: null,
		POLL_INTERVAL: 10000,

		init: function () {
			this.bindEvents();
			this.startPolling();
		},

		bindEvents: function () {
			var self = this;

			$( document ).on( 'click', '.waxap-conv-item', function () {
				var phone = $( this ).data( 'phone' );
				self.openThread( phone );
			} );

			$( '#waxap-send-btn' ).on( 'click', function () {
				self.sendMessage();
			} );

			$( '#waxap-send-text' ).on( 'keydown', function ( e ) {
				if ( e.key === 'Enter' && ( e.ctrlKey || e.metaKey ) ) {
					self.sendMessage();
				}
			} );
		},

		startPolling: function () {
			var self = this;
			self.pollTimer = setInterval( function () {
				if ( document.visibilityState === 'hidden' ) return;
				self.pollConversations();
				if ( self.currentPhone ) {
					self.pollThread( self.currentPhone );
				}
			}, self.POLL_INTERVAL );
		},

		pollConversations: function () {
			var self = this;
			$.post( waxapInbox.ajaxUrl, {
				action: 'wa_notifier_inbox_conversations',
				nonce: waxapInbox.nonce,
			} ).done( function ( res ) {
				if ( res.success ) {
					self.renderConversations( res.data );
				}
			} );
		},

		pollThread: function ( phone ) {
			var self = this;
			$.post( waxapInbox.ajaxUrl, {
				action: 'wa_notifier_inbox_thread',
				nonce: waxapInbox.nonce,
				phone: phone,
			} ).done( function ( res ) {
				if ( res.success && self.currentPhone === phone ) {
					self.renderMessages( res.data );
				}
			} );
		},

		openThread: function ( phone ) {
			var self = this;
			self.currentPhone = phone;

			$( '.waxap-conv-item' ).removeClass( 'active' );
			$( '.waxap-conv-item[data-phone="' + phone + '"]' ).addClass( 'active' );
			$( '#waxap-thread-header-phone' ).text( '+' + phone );
			$( '#waxap-inbox-empty' ).hide();
			$( '#waxap-thread' ).show();
			$( '#waxap-thread-messages' ).html( '<div class="waxap-thread-loading">Cargando...</div>' );

			$.post( waxapInbox.ajaxUrl, {
				action: 'wa_notifier_inbox_thread',
				nonce: waxapInbox.nonce,
				phone: phone,
			} ).done( function ( res ) {
				if ( res.success ) {
					self.renderMessages( res.data );
					// Mark as read and clear badge
					self.markRead( phone );
				} else {
					$( '#waxap-thread-messages' ).html( '<div class="waxap-thread-error">Error al cargar mensajes.</div>' );
				}
			} );
		},

		markRead: function ( phone ) {
			$( '.waxap-conv-item[data-phone="' + phone + '"] .waxap-unread-badge' ).remove();
			$.post( waxapInbox.ajaxUrl, {
				action: 'wa_notifier_inbox_read',
				nonce: waxapInbox.nonce,
				phone: phone,
			} );
		},

		sendMessage: function () {
			var self = this;
			var $textarea = $( '#waxap-send-text' );
			var text = $.trim( $textarea.val() );
			if ( ! text || ! self.currentPhone ) return;

			var $btn = $( '#waxap-send-btn' );
			$btn.prop( 'disabled', true ).text( '...' );

			$.post( waxapInbox.ajaxUrl, {
				action: 'wa_notifier_inbox_send',
				nonce: waxapInbox.nonce,
				phone: self.currentPhone,
				text: text,
			} ).done( function ( res ) {
				if ( res.success ) {
					$textarea.val( '' );
					// Optimistic append
					var now = new Date().toISOString();
					self.appendMessage( { direction: 'outbound', body: text, createdAt: now } );
				} else {
					alert( res.data || 'Error al enviar el mensaje.' );
				}
			} ).always( function () {
				$btn.prop( 'disabled', false ).text( 'Enviar' );
				$textarea.focus();
			} );
		},

		renderConversations: function ( conversations ) {
			var self = this;
			var $list = $( '#waxap-conv-list' );
			if ( ! conversations || ! conversations.length ) {
				$list.html( '<div class="waxap-conv-empty">Sin conversaciones todavía.</div>' );
				return;
			}
			var html = '';
			$.each( conversations, function ( i, conv ) {
				var isActive = self.currentPhone === conv.phone ? ' active' : '';
				var preview = conv.lastMessage && conv.lastMessage.body
					? conv.lastMessage.body.substring( 0, 45 ) + ( conv.lastMessage.body.length > 45 ? '…' : '' )
					: '—';
				var timeStr = conv.lastMessage && conv.lastMessage.createdAt
					? self.formatTime( conv.lastMessage.createdAt )
					: '';
				var badge = conv.unreadCount > 0 && self.currentPhone !== conv.phone
					? '<span class="waxap-unread-badge">' + conv.unreadCount + '</span>'
					: '';
				var initial = conv.phone.charAt( 0 );
				html += '<div class="waxap-conv-item' + isActive + '" data-phone="' + self.escAttr( conv.phone ) + '">';
				html += '<div class="waxap-conv-avatar">' + initial + '</div>';
				html += '<div class="waxap-conv-info">';
				html += '<div class="waxap-conv-phone">+' + self.escHtml( conv.phone ) + '</div>';
				html += '<div class="waxap-conv-preview">' + self.escHtml( preview ) + '</div>';
				html += '</div>';
				html += '<div class="waxap-conv-meta"><span class="waxap-conv-time">' + self.escHtml( timeStr ) + '</span>' + badge + '</div>';
				html += '</div>';
			} );
			$list.html( html );
		},

		renderMessages: function ( messages ) {
			var self = this;
			var $container = $( '#waxap-thread-messages' );
			if ( ! messages || ! messages.length ) {
				$container.html( '<div class="waxap-thread-empty-msgs">Sin mensajes aún.</div>' );
				return;
			}
			var html = '';
			$.each( messages, function ( i, msg ) {
				var dir = msg.direction === 'outbound' ? 'outbound' : 'inbound';
				var timeStr = self.formatTime( msg.createdAt );
				var body = msg.body || '';
				html += '<div class="waxap-msg waxap-msg-' + dir + '">';
				html += self.escHtml( body );
				html += '<div class="waxap-msg-time">' + self.escHtml( timeStr ) + '</div>';
				html += '</div>';
			} );
			$container.html( html );
			$container.scrollTop( $container[0].scrollHeight );
		},

		appendMessage: function ( msg ) {
			var self = this;
			var $container = $( '#waxap-thread-messages' );
			var dir = msg.direction === 'outbound' ? 'outbound' : 'inbound';
			var timeStr = self.formatTime( msg.createdAt );
			var $msg = $( '<div class="waxap-msg waxap-msg-' + dir + '">' );
			$msg.text( msg.body || '' );
			$msg.append( '<div class="waxap-msg-time">' + self.escHtml( timeStr ) + '</div>' );
			$container.append( $msg );
			$container.scrollTop( $container[0].scrollHeight );
		},

		formatTime: function ( iso ) {
			if ( ! iso ) return '';
			try {
				var d = new Date( iso );
				var now = new Date();
				var isToday = d.toDateString() === now.toDateString();
				if ( isToday ) {
					return d.toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } );
				}
				return d.toLocaleDateString( [], { day: '2-digit', month: '2-digit' } );
			} catch ( e ) {
				return '';
			}
		},

		escHtml: function ( str ) {
			return $( '<div>' ).text( String( str ) ).html();
		},

		escAttr: function ( str ) {
			return String( str ).replace( /"/g, '&quot;' );
		},
	};

	$( function () {
		if ( $( '#waxap-inbox' ).length ) {
			WaxapInboxApp.init();
		}
	} );
} )( jQuery );
