/**
 * Modern Tabby Tabs — front-end behaviour.
 * Vanilla JS (ES2017+), no dependencies.
 */
( function () {
	'use strict';

	var BREAKPOINT = 767;

	var C = {
		enabled:        'responsive-tabs--enabled',
		heading:        'responsive-tabs__heading',
		headingActive:  'responsive-tabs__heading--active',
		panel:          'responsive-tabs__panel',
		panelActive:    'responsive-tabs__panel--active',
		list:           'responsive-tabs__list',
		listItem:       'responsive-tabs__list__item',
		listItemActive: 'responsive-tabs__list__item--active',
		wrapper:        'responsive-tabs-wrapper'
	};

	var S = {
		group: '.responsive-tabs:not(.' + C.enabled + ')',
		title: 'tabtitle',
		panel: 'tabcontent'
	};

	// Debug logging is opt-in: set window.MTT_DEBUG = true in the console.
	var DEBUG = window.MTT_DEBUG === true;

	function log() {
		if ( DEBUG && window.console && console.log ) {
			console.log.apply( console, [ '[mrt]' ].concat( [].slice.call( arguments ) ) );
		}
	}

	function warn() {
		if ( window.console && console.warn ) {
			console.warn.apply( console, [ '[mrt]' ].concat( [].slice.call( arguments ) ) );
		}
	}

	function isMobile() {
		return window.matchMedia( '(max-width: ' + BREAKPOINT + 'px)' ).matches;
	}

	function hasClass( el, name ) {
		return el.classList && el.classList.contains( name );
	}

	/**
	 * Whether the user asked the system to reduce motion.
	 */
	function prefersReducedMotion() {
		return !! (
			window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Smooth scrolling unless reduced motion is requested.
	 */
	function scrollBehavior() {
		return prefersReducedMotion() ? 'auto' : 'smooth';
	}

	/**
	 * Scrolls the page so a document coordinate sits at the viewport top.
	 */
	function scrollPageTo( top ) {
		window.scrollTo( {
			top: Math.max( 0, top ),
			behavior: scrollBehavior()
		} );
	}

	/**
	 * Brings the start of the active panel back into view.
	 *
	 * Switching from a tall panel to a short one leaves the page scrolled past
	 * the new, shorter panel. Only in that case (the panel top is above the
	 * viewport) is the page moved; an already visible panel is left alone.
	 * The anchor is the tab strip on desktop and the tab heading on mobile
	 * (where the strip is hidden), so the active tab stays visible too.
	 */
	function revealPanelStart( panel, tabList, heading ) {
		if ( panel.getBoundingClientRect().top >= 0 ) {
			return;
		}

		var anchor = isMobile() ? heading : tabList;
		scrollPageTo( window.pageYOffset + anchor.getBoundingClientRect().top );
	}

	/**
	 * Moves focus into the freshly activated panel without a second scroll.
	 */
	function focusPanel( panel ) {
		panel.focus( { preventScroll: true } );
	}

	/* ------------------------------------------------------------------ */

	function init() {
		var groups = document.querySelectorAll( S.group );

		if ( ! groups.length ) {
			return;
		}

		log( 'initialising', groups.length, 'group(s)' );

		for ( var i = 0; i < groups.length; i++ ) {
			try {
				setup( groups[ i ] );
			} catch ( err ) {
				warn( 'setup failed for group', groups[ i ], err );
			}
		}
	}

	/**
	 * Collect our children by class, drilling into <p> wrappers that wpautop
	 * may have inserted between the group and its elements.
	 */
	function collect( parent, className ) {
		var result = [];
		var kids = parent.children;

		for ( var i = 0; i < kids.length; i++ ) {
			var el = kids[ i ];

			if ( hasClass( el, className ) ) {
				result.push( el );
				continue;
			}

			if ( el.tagName === 'P' && el.children.length ) {
				var nested = collect( el, className );
				for ( var j = 0; j < nested.length; j++ ) {
					result.push( nested[ j ] );
				}
			}
		}

		return result;
	}

	function setup( root ) {
		root.classList.add( C.enabled );

		var headings = collect( root, S.title );
		var panels   = collect( root, S.panel );

		log( 'group:', root, 'headings:', headings.length, 'panels:', panels.length );

		if ( ! headings.length || headings.length !== panels.length ) {
			warn( 'structure mismatch — check markup', {
				root: root,
				headings: headings,
				panels: panels
			} );
			return;
		}

		// Defensive: guarantee exactly one panel is marked active.
		// (PHP already sets the class on the active panel; this is a safety net.)
		var hasActive = false;
		for ( var p = 0; p < panels.length; p++ ) {
			if ( hasClass( panels[ p ], C.panelActive ) ) {
				hasActive = true;
				break;
			}
		}
		if ( ! hasActive ) {
			panels[ 0 ].classList.add( C.panelActive );
		}

		// Ensure a wrapper exists so we can lock height during switches.
		var wrapper = root.parentElement;
		if ( ! wrapper || ! hasClass( wrapper, C.wrapper ) ) {
			wrapper = document.createElement( 'div' );
			wrapper.className = C.wrapper;
			root.parentNode.insertBefore( wrapper, root );
			wrapper.appendChild( root );
		}

		var groupUid = root.getAttribute( 'data-mrt-group' ) ||
			( 'mrt-' + Math.random().toString( 36 ).slice( 2, 8 ) );

		var tabList = document.createElement( 'ul' );
		tabList.className = C.list;
		tabList.setAttribute( 'role', 'tablist' );

		for ( var i = 0; i < headings.length; i++ ) {
			buildTab( headings, panels, i, groupUid, tabList, wrapper );
		}

		root.insertBefore( tabList, root.firstChild );

		// Apply initial state.
		for ( var k = 0; k < panels.length; k++ ) {
			var panel = panels[ k ];
			var active = hasClass( panel, C.panelActive );

			panel.hidden = ! active;
			panel.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			headings[ k ].classList.toggle( C.headingActive, active );
		}

		wrapper.addEventListener( 'transitionend', function () {
			wrapper.style.height = '';
		} );
	}

	/* ------------------------------------------------------------------ */

	function buildTab( headings, panels, i, groupUid, tabList, wrapper ) {
		var heading = headings[ i ];
		var panel   = panels[ i ];
		var tabId   = groupUid + '-tab-' + ( i + 1 );
		var panelId = panel.id || ( groupUid + '-panel-' + ( i + 1 ) );

		panel.id = panelId;

		heading.classList.add( C.heading );
		panel.classList.add( C.panel );

		heading.setAttribute( 'role', 'tab' );
		heading.setAttribute( 'tabindex', '0' );
		heading.setAttribute( 'aria-controls', panelId );

		if ( ! heading.id ) {
			heading.id = tabId + '-heading';
		}

		panel.setAttribute( 'role', 'tabpanel' );
		panel.setAttribute( 'aria-labelledby', tabId );
		panel.setAttribute( 'tabindex', '0' );

		var active = hasClass( panel, C.panelActive );

		var item = document.createElement( 'li' );
		item.className = C.listItem;
		item.id = tabId;
		item.setAttribute( 'role', 'tab' );
		item.setAttribute( 'aria-controls', panelId );
		item.setAttribute( 'aria-selected', active ? 'true' : 'false' );
		item.setAttribute( 'tabindex', active ? '0' : '-1' );
		item.innerHTML = heading.innerHTML;
		item.classList.toggle( C.listItemActive, active );

		var activate = function ( fromKeyboard ) {
			selectTab( headings, panels, tabList, wrapper, i, fromKeyboard === true );
		};

		var toggleHeading = function ( fromKeyboard ) {
			if ( isMobile() && hasClass( panel, C.panelActive ) ) {
				collapsePanel( panel, heading );
			} else {
				activate( fromKeyboard );
			}
		};

		item.addEventListener( 'click', function () {
			activate( false );
		} );
		item.addEventListener( 'keydown', function ( e ) {
			onTabKey( e, tabList, i, activate );
		} );

		heading.addEventListener( 'click', function () {
			toggleHeading( false );
		} );

		heading.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				toggleHeading( true );
			}
		} );

		tabList.appendChild( item );
	}

	function onTabKey( event, tabList, index, activate ) {
		switch ( event.key ) {
			case 'Enter':
			case ' ':
				event.preventDefault();
				activate( true );
				break;

			case 'ArrowRight':
				event.preventDefault();
				moveFocus( tabList, index, 1 );
				break;

			case 'ArrowLeft':
				event.preventDefault();
				moveFocus( tabList, index, -1 );
				break;

			case 'Home':
				event.preventDefault();
				if ( tabList.firstElementChild ) {
					tabList.firstElementChild.focus();
				}
				break;

			case 'End':
				event.preventDefault();
				if ( tabList.lastElementChild ) {
					tabList.lastElementChild.focus();
				}
				break;
		}
	}

	function moveFocus( tabList, index, delta ) {
		var items = [].slice.call( tabList.children );
		var next  = ( index + delta + items.length ) % items.length;
		items[ next ].focus();
	}

	/**
	 * Collapses an accordion section on mobile.
	 *
	 * The heading can be above the viewport when the section above it is
	 * longer than the screen; recover it so the user is not left in empty
	 * space after the panel disappears.
	 */
	function collapsePanel( panel, heading ) {
		panel.classList.remove( C.panelActive );
		heading.classList.remove( C.headingActive );
		panel.hidden = true;
		panel.setAttribute( 'aria-hidden', 'true' );

		var top = heading.getBoundingClientRect().top;
		if ( top < 0 ) {
			scrollPageTo( window.pageYOffset + top );
		}
	}

	function selectTab( headings, panels, tabList, wrapper, index, fromKeyboard ) {
		wrapper.style.height = wrapper.offsetHeight + 'px';

		for ( var i = 0; i < panels.length; i++ ) {
			var active = i === index;
			panels[ i ].hidden = ! active;
			panels[ i ].setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			panels[ i ].classList.toggle( C.panelActive, active );
			headings[ i ].classList.toggle( C.headingActive, active );
		}

		var items = [].slice.call( tabList.children );
		for ( var k = 0; k < items.length; k++ ) {
			var isActive = k === index;
			items[ k ].classList.toggle( C.listItemActive, isActive );
			items[ k ].setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			items[ k ].setAttribute( 'tabindex', isActive ? '0' : '-1' );
		}

		revealPanelStart( panels[ index ], tabList, headings[ index ] );

		// Keyboard activation moves focus into the new content, so a screen
		// reader and the Tab key continue inside the panel. A pointer click
		// keeps focus where the browser put it.
		if ( fromKeyboard === true ) {
			focusPanel( panels[ index ] );
		}

		window.requestAnimationFrame( function () {
			wrapper.style.height = '';
		} );

		window.dispatchEvent( new Event( 'resize' ) );
	}

	/* ------------------------------------------------------------------ */

	if ( window.MutationObserver ) {
		new MutationObserver( function ( mutations ) {
			for ( var m = 0; m < mutations.length; m++ ) {
				var added = mutations[ m ].addedNodes;
				for ( var n = 0; n < added.length; n++ ) {
					var node = added[ n ];
					if ( node.nodeType !== 1 ) {
						continue;
					}
					if (
						( node.matches && node.matches( S.group ) ) ||
						( node.querySelector && node.querySelector( S.group ) )
					) {
						init();
						return;
					}
				}
			}
		} ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();