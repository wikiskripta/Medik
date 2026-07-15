<?php

use MediaWiki\MediaWikiServices;

/**
 * BaseTemplate class for the Medik skin
 *
 * @ingroup Skins
 */
class MedikTemplate extends BaseTemplate {
	
	private $templateParserClass;
	private $htmlClass;
	private $linkerClass;

	/**
	 * Outputs the entire contents of the page
	 * (uses templates/skin.mustache as a template)
	 */
	public function execute() {
		$this->resolveMwVersion();
		$templateParser = new $this->templateParserClass( __DIR__ . '/../templates' );
		$contentWidth = [
			'full' => 'col-xl-10',
			'default' => 'col-xl-9',
			'narrow' => 'col-xl-8',
		];

		echo $templateParser->processTemplate( 'skin', [
			'medik-color' => RequestContext::getMain()->getConfig()->get( 'MedikColor' ),
			'html-logo' => $this->getLogo(),
			'html-search-userlinks' => $this->getSearch() . $this->getUserLinks(),
			'html-navigation-heading' => $this->getMsg( 'navigation-heading' )->parse(),
			'html-site-navigation' => $this->getSiteNavigation(),
			'medik-content-width' => $contentWidth[
				RequestContext::getMain()->getConfig()->get( 'MedikContentWidth' )
				] ?? $contentWidth['default'],
			'html-sitenotice' => $this->getSiteNotice(),
			'html-talknotice' => $this->getNewTalk(),
			'html-aside' => $this->getAside(),
			'html-indicators' => $this->getIndicators(),
			'pagelanguage' => $this->get( 'pageLanguage' ),
			'html-pagetitle' => $this->get( 'title' ),
			'html-tagline' => $this->getMsg( 'tagline' )->parse(),
			'html-pagesubtitle' => $this->getPageSubtitle(),
			'html-undelete' => $this->get( 'undelete' ),
			'html-bodycontent' => $this->get( 'bodycontent' ),
			'html-clear' => $this->getClear(),
			'html-printfooter' => $this->get( 'printfooter' ),
			'html-categorylinks' => $this->getCategoryLinks(),
			'html-dataaftercontent' => $this->getDataAfterContent() . $this->get( 'debughtml' ),
			'html-footer' => $this->getFooterBlock(),
		] );
	}

	/**
	 * This is needed to resolve the differences with the TemplateParser, Html, and Linker classes
	 * between MediaWiki 1.43 and 1.44
	 */
	protected function resolveMwVersion() {
		if ( version_compare( MW_VERSION, '1.40', '>=' ) ) {
			$this->templateParserClass = 'MediaWiki\Html\TemplateParser';
			$this->htmlClass = 'MediaWiki\Html\Html';
			$this->linkerClass = 'MediaWiki\linker\Linker';
		} else {
			$this->templateParserClass = 'TemplateParser';
			$this->htmlClass = 'Html';
			$this->linkerClass = 'Linker';
		}
	}

	/**
	 * Generates the site title
	 * @param string $id
	 *
	 * @return string html
	 */
	protected function getLogo( $id = 'p-logo' ) {
		$html = $this->htmlClass::openElement(
			'div',
			[
				'id' => $id,
				'class' => 'mw-portlet',
				'role' => 'banner'
			]
		);

		// Hamburger menu
		$html .= $this->htmlClass::element( 'span', [ 'class' => 'mw-hamb' ] );

		// Site title
		$siteTitle = $this->htmlClass::element(
			'span',
			[
				'class' => 'mw-desktop-sitename'
			],
			RequestContext::getMain()->getConfig()->get( 'Sitename' )
		);
		$siteMobileTitle = $this->htmlClass::element(
			'span',
			[
				'class' => 'mw-mobile-sitename'
			],
			RequestContext::getMain()->getConfig()->get( 'MedikMobileSitename' ) ??
				RequestContext::getMain()->getConfig()->get( 'Sitename' )
		);
		$logoWidth = RequestContext::getMain()->getConfig()->get( 'MedikLogoWidth' );
		$siteLogo = ( RequestContext::getMain()->getConfig()->get( 'MedikShowLogo' ) === 'main' ?
			$this->htmlClass::rawElement(
				'span',
				[
					'class' => 'mw-wiki-logo',
					'style' => (
						$logoWidth !== 'none' ?
							'width: ' . $logoWidth . ';' :
							''
					)
				]
			) :
			''
		);

		$html .= $this->htmlClass::rawElement(
			'a',
			[
				'id' => 'p-banner',
				'class' => 'mw-wiki-title navbar-brand',
				'href' => $this->data['nav_urls']['mainpage']['href']
			] + $this->linkerClass::tooltipAndAccesskeyAttribs( 'p-logo' ),
			$siteLogo .
			( RequestContext::getMain()->getConfig()->get( 'MedikUseLogoWithoutText' ) ?
				'' :
				$siteTitle . ' ' . $siteMobileTitle
			)
		);

		$html .= $this->htmlClass::closeElement( 'div' );

		return $html;
	}

	/**
	 * Generates the search form
	 *
	 * @return string html
	 */
	protected function getSearch() {
		$html = $this->htmlClass::openElement(
			'form',
			[
				'action' => $this->get( 'wgScript' ),
				'role' => 'search',
				'class' => 'mw-portlet form-inline my-lg-0',
				'id' => 'p-search'
			]
		);
		$html .= $this->htmlClass::hidden( 'title', $this->get( 'searchtitle' ) );
		$html .= $this->htmlClass::rawElement(
			'h3',
			[ 'hidden' ],
			$this->htmlClass::label( $this->getMsg( 'search' )->text(), 'searchInput' )
		);
		$html .= $this->getSkin()->makeSearchInput( [ 'id' => 'searchInput', 'class' => 'form-control mr-sm-2' ] );
		$html .= $this->getSkin()->makeSearchButton(
			'go',
			[
				'hidden',
				'id' => 'searchGoButton',
				'class' => 'searchButton btn btn-outline-dark my-2 my-sm-0'
			]
		);
		$html .= $this->htmlClass::closeElement( 'form' );

		return $html;
	}

	/**
	 * Generates aside edit menu
	 * @return string html
	 */
	protected function getAside() {
		$html = $this->htmlClass::openElement( 'aside' );

		$html .= $this->htmlClass::rawElement(
			'div',
			[ 'class' => 'd-flex flex-row flex-wrap' ],
			$this->getPortlet(
				'namespaces',
				$this->data['content_navigation']['namespaces'],
				null,
				[ 'portlet-list-tag' => 'div', 'list-item' => [ 'tag' => 'span' ] ]
			) .
			$this->htmlClass::rawElement( 'div', [ 'class' => 'dropdown' ],
				$this->htmlClass::element(
					'a',
					[
						'class' => 'dropdown-toggle ',
						'role' => 'button',
						'data-bs-toggle' => 'dropdown',
						'data-bs-display' => 'static',
						'aria-haspopup' => 'true',
						'aria-expanded' => 'false'
					],
					$this->getMsg( 'actions' )->text()
				) .
				$this->htmlClass::rawElement(
					'div',
					[ 'class' => 'dropdown-menu dropdown-menu-end' ],
					$this->getPageLinks()
				)
			) .
			$this->htmlClass::rawElement( 'div', [ 'class' => 'dropdown' ],
				$this->htmlClass::element(
					'a',
					[
						'class' => 'dropdown-toggle ',
						'role' => 'button',
						'data-bs-toggle' => 'dropdown',
						'data-bs-display' => 'static',
						'aria-haspopup' => 'true',
						'aria-expanded' => 'false'
					],
					$this->getMsg( 'toolbox' )->text()
				) .
				$this->htmlClass::rawElement(
					'div',
					[ 'class' => 'dropdown-menu dropdown-menu dropdown-menu-end' ],
					$this->getPortlet(
						'tb',
						$this->data['sidebar']['TOOLBOX'],
						'toolbox',
						[ 'add-class' => 'dropdown-item' ]
					)
				)
			)
		);

		$html .= $this->htmlClass::closeElement( 'aside' );

		return $html;
	}

	/**
	 * Generates the sidebar
	 * Set the elements to true to allow them to be part of the sidebar
	 * Or get rid of this entirely, and take the specific bits to use
	 * wherever you actually want them
	 * 	- Toolbox is the page/site tools that appears under the sidebar in vector
	 * 	- Languages is the interlanguage links on the page via en:... es:... etc
	 * 	- Default is each user-specified box as defined on MediaWiki:Sidebar;
	 *    you will still need a foreach loop to parse these.
	 *
	 * @return string html
	 */
	protected function getSiteNavigation() {
		$html = '';

		$html .= ( RequestContext::getMain()->getConfig()->get( 'MedikShowLogo' ) === 'sidebar' ?
			$this->htmlClass::rawElement(
				'div',
				[
					'class' => 'mw-wiki-navigation-logo'
				],
				$this->htmlClass::rawElement(
					'a',
					[
						'class' => 'mw-wiki-logo',
						'style' => ( RequestContext::getMain()->getConfig()->get( 'MedikContentWidth' ) === 'wide' ?
							'height: clamp(4em, 4vw, 10em); width: clamp(4em, 60%, 10em);' :
							'' ),
						'href' => $this->data['nav_urls']['mainpage']['href']
					]
				)
			) :
			''
		);

		$sidebar = $this->getSidebar();
		$sidebar['SEARCH'] = false;
		$sidebar['TOOLBOX'] = false;

		foreach ( $sidebar as $name => $content ) {
			if ( $content === false ) {
				continue;
			}
			// Numeric strings gets an integer when set as key, cast back - T73639
			$name = (string)$name;

			switch ( $name ) {
				case 'SEARCH':
					$html .= $this->getSearch();
					break;
				case 'TOOLBOX':
					$html .= $this->getPortlet(
						'tb',
						$this->data['sidebar']['TOOLBOX'],
						'toolbox'
					);
					break;
				case 'LANGUAGES':
					$html .= $this->getPortlet(
						'lang',
						$content['content'],
						'otherlanguages',
						[ 'add-class' => 'nav-link' ]
					);
					break;
				default:
					$html .= $this->getPortlet(
						$name,
						$content[ 'content' ],
						null,
						[ 'add-class' => 'nav-link' ]
					);
					break;
			}
		}
		return $html;
	}

	/**
	 * Generates page-related tools/links
	 * You will probably want to split this up and move all of these
	 * to somewhere that makes sense for your skin.
	 *
	 * @return string html
	 */
	protected function getPageLinks() {
		// 'View' actions for the page: view, edit, view history, etc
		$html = $this->getPortlet(
			'views',
			$this->data['content_navigation']['views'],
			null,
			[ 'add-class' => 'dropdown-item' ]
		);
		// Other actions for the page: move, delete, protect, everything else
		$html .= $this->getPortlet(
			'actions',
			$this->data['content_navigation']['actions'],
			null,
			[ 'add-class' => 'dropdown-item' ]
		);

		return $html;
	}

	/**
	 * Generates user tools menu
	 * @return string html
	 */
	protected function getUserLinks() {
		$personaltools = $this->getPersonalTools();
		// Remove Echo icons from the personal menu
		$echoicons = [];
		if ( isset( $personaltools[ 'notifications-alert' ] ) ) {
			$echoicons[ 'notifications-alert' ] = $personaltools[ 'notifications-alert' ];
			unset( $personaltools[ 'notifications-alert' ] );
		}
		if ( isset( $personaltools[ 'notifications-notice' ] ) ) {
			$echoicons[ 'notifications-notice' ] = $personaltools[ 'notifications-notice' ];
			unset( $personaltools[ 'notifications-notice' ] );
		}
		// HTML start
		$html = '';
		// Echo icons
		if ( !empty( $echoicons ) ) {
			$icons = '';
			foreach ( $echoicons as $key => $item ) {
				$icons .= $this->getSkin()->makeListItem( $key, $item );
			}
			$html .= $this->htmlClass::rawElement(
				'div',
				[ 'id' => 'personal-echo-icons' ],
				$this->htmlClass::rawElement( 'ul', [], $icons )
			);
		}
		// User tools
		$html .= $this->htmlClass::openElement(
							'div',
							[ 'id' => 'user-tools', 'class' => 'btn-group' ]
						);

		// User icon for smaller screens
		$html .= $this->htmlClass::rawElement(
							 'div',
							 [ 'class' => 'profile-icon' ],
							 ''
						 );

		// Splitted dropdown button (with username or login option)
		$html .= $this->htmlClass::rawElement(
							 'a',
							 [ 'href' =>
								 $personaltools['userpage']['links'][0]['href'] ??
									$personaltools['login']['links'][0]['href'] ??
									$personaltools['login-private']['links'][0]['href'] ],
							 $this->htmlClass::element(
								 'button',
								 [
									 'class' => 'btn btn-link',
								 ],
								 $personaltools['userpage']['links'][0]['text'] ??
									$this->getMsg( 'login' )->text()
							 )
						 ) .
						 $this->htmlClass::rawElement(
							 'button',
							 [
								 'class' => 'btn btn-link dropdown-toggle dropdown-toggle-split',
								 'type' => 'button',
								 'data-bs-toggle' => 'dropdown',
								 'aria-haspopup' => 'true',
								 'aria-expanded' => 'false'
							 ],
							 $this->htmlClass::rawElement( 'span', [ 'class' => 'visually-hidden' ], '&darr;' )
						 );

		// Basic list output
		$html .= $this->htmlClass::rawElement(
							 'div',
							 [ 'class' => 'dropdown-menu dropdown-menu-end' ],
							 $this->getPortlet(
								 'personal',
								 $personaltools,
								 'personaltools',
								 [ 'add-class' => 'dropdown-item' ]
							 )
						 );

		$html .= $this->htmlClass::closeElement( 'div' );

		return $html;
	}

	/**
	 * Generates siteNotice, if any
	 * @return string html
	 */
	protected function getSiteNotice() {
		return $this->getIfExists( 'sitenotice', [
			'wrapper' => 'div',
			'parameters' => [ 'id' => 'siteNotice' ]
		] );
	}

	/**
	 * Generates new talk message banner, if any
	 * @return string html
	 */
	protected function getNewTalk() {
		return $this->getIfExists( 'newtalk', [
			'wrapper' => 'div',
			'parameters' => [ 'class' => 'usermessage' ]
		] );
	}

	/**
	 * Generates subtitle stuff, if any
	 * @return string html
	 */
	protected function getPageSubtitle() {
		return $this->getIfExists( 'subtitle', [ 'wrapper' => 'p' ] );
	}

	/**
	 * Generates category links, if any
	 * @return string html
	 */
	protected function getCategoryLinks() {
		return $this->getIfExists( 'catlinks' );
	}

	/**
	 * Generates data after content stuff, if any
	 * @return string html
	 */
	protected function getDataAfterContent() {
		return $this->getIfExists( 'dataAfterContent' );
	}

	/**
	 * Simple wrapper for random if-statement-wrapped $this->data things
	 *
	 * @param string $object name of thing
	 * @param array $setOptions
	 *
	 * @return string html
	 */
	protected function getIfExists( $object, $setOptions = [] ) {
		$options = $setOptions + [
			'wrapper' => 'none',
			'parameters' => []
		];

		$html = '';

		if ( $this->data[$object] ) {
			if ( $options['wrapper'] == 'none' ) {
				$html .= $this->get( $object );
			} else {
				$html .= $this->htmlClass::rawElement(
					$options['wrapper'],
					$options['parameters'],
					$this->get( $object )
				);
			}
		}

		return $html;
	}

	/**
	 * Generates a block of navigation links with a header
	 *
	 * @param string $name
	 * @param array|string $content array of links for use with makeListItem, or a block of text
	 * @param null|string|array $msg
	 * @param array $setOptions random crap to rename/do/whatever
	 *
	 * @return string html
	 */
	protected function getPortlet( $name, $content, $msg = null, $setOptions = [] ) {
		// random stuff to override with any provided options
		$options = $setOptions + [
			// extra classes/ids
			'id' => 'p-' . $name,
			'class' => 'mw-portlet',
			'extra-classes' => '',
			// what to wrap the body list in, if anything
			'body-wrapper' => 'div',
			'body-id' => null,
			'body-class' => 'mw-portlet-body',
			'portlet-list-tag' => 'ul',
			// option to stick arbitrary stuff at the beginning of the ul
			'list-prepend' => '',
			// old toolbox hook support (use: [ 'SkinTemplateToolboxEnd' => [ &$skin, true ] ])
			'hooks' => '',
			// what to pass to makeListItem() as options array
			'list-item' => []
		];

		// Handle the different $msg possibilities
		if ( $msg === null ) {
			$msg = $name;
		} elseif ( is_array( $msg ) ) {
			$msgString = array_shift( $msg );
			$msgParams = $msg;
			$msg = $msgString;
		}
		$msgObj = $this->getMsg( $msg );
		if ( $msgObj->exists() ) {
			if ( isset( $msgParams ) && !empty( $msgParams ) ) {
				$msgString = $this->getMsg( $msg, $msgParams )->parse();
			} else {
				$msgString = $msgObj->parse();
			}
		} else {
			$msgString = htmlspecialchars( $msg );
		}

		$labelId = Sanitizer::escapeIdForAttribute( "p-$name-label" );

		if ( is_array( $content ) ) {
			if ( count( $content ) === 0 ) { return;
			}
			$contentText = $this->htmlClass::openElement( $options['portlet-list-tag'],
				[ 'lang' => $this->get( 'userlang' ), 'dir' => $this->get( 'dir' ) ]
			);
			$contentText .= $options['list-prepend'];
			foreach ( $content as $key => $item ) {
				if ( isset( $options['add-class'] ) ) {
					if ( isset( $item['link-class'] ) ) {
						$item['link-class'] .= " {$options['add-class']}";
					} else {
						$item['link-class'] = " {$options['add-class']}";
					}
				}
				$contentText .= $this->getSkin()->makeListItem( $key, $item, $options['list-item'] );
			}
			// Compatibility with extensions still using SkinTemplateToolboxEnd or similar
			if ( is_array( $options['hooks'] ) ) {
				foreach ( $options['hooks'] as $hook ) {
					if ( is_string( $hook ) ) {
						$hookOptions = [];
					} else {
						// it should only be an array otherwise
						$hookOptions = array_values( $hook )[0];
						$hook = array_keys( $hook )[0];
					}
					$contentText .= $this->deprecatedHookHack( $hook, $hookOptions );
				}
			}

			$contentText .= $this->htmlClass::closeElement( $options['portlet-list-tag'] );
		} else {
			$contentText = $content;
		}

		// Special handling for role=search and other weird things
		$divOptions = [
			'role' => 'navigation',
			'id' => Sanitizer::escapeIdForAttribute( $options['id'] ),
			'title' => $this->linkerClass::titleAttrib( $options['id'] ),
			'aria-labelledby' => $labelId
		];
		if ( !is_array( $options['class'] ) ) {
			$class = [ $options['class'] ];
		}
		if ( !is_array( $options['extra-classes'] ) ) {
			$extraClasses = [ $options['extra-classes'] ];
		}
		$divOptions['class'] = array_merge( $class, $extraClasses );

		$labelOptions = [
			'id' => $labelId,
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' ),
			'class' => 'nav-link disabled',
			'href' => '#',
			'role' => 'button'
		];

		if ( $options['body-wrapper'] !== 'none' ) {
			$bodyDivOptions = [ 'class' => $options['body-class'] ];
			if ( is_string( $options['body-id'] ) ) {
				$bodyDivOptions['id'] = $options['body-id'];
			}
			$body = $this->htmlClass::rawElement( $options['body-wrapper'], $bodyDivOptions,
				$contentText .
				$this->getSkin()->getAfterPortlet( $name )
			);
		} else {
			$body = $contentText . $this->getSkin()->getAfterPortlet( $name );
		}

		$html = $this->htmlClass::rawElement( 'div', $divOptions,
			$this->htmlClass::rawElement( 'a', $labelOptions, $msgString ) .
			$body
		);

		return $html;
	}

	/**
	 * Wrapper to catch output of old hooks expecting to write directly to page
	 * We no longer do things that way.
	 *
	 * @param string $hook event
	 * @param array $hookOptions args
	 *
	 * @return string html
	 */
	protected function deprecatedHookHack( $hook, $hookOptions = [] ) {
		$hookContents = '';
		ob_start();
		Hooks::run( $hook, $hookOptions );
		$hookContents = ob_get_contents();
		ob_end_clean();
		if ( !trim( $hookContents ) ) {
			$hookContents = '';
		}

		return $hookContents;
	}

	/**
	 * Better renderer for getFooterIcons and getFooterLinks, based on Vector
	 *
	 * @param array $setOptions Miscellaneous other options
	 * * 'id' for footer id
	 * * 'class' for footer class
	 * * 'order' to determine whether icons or links appear first: 'iconsfirst' or links, though in
	 * 	 practice we currently only check if it is or isn't 'iconsfirst'
	 * * 'link-prefix' to set the prefix for all link and block ids; most skins use 'f' or 'footer',
	 * 	 as in id='f-whatever' vs id='footer-whatever'
	 * * 'link-style' to pass to getFooterLinks: "flat" to disable categorisation of links in a
	 * 	 nested array
	 *
	 * @return string html
	 */
	protected function getFooterBlock( $setOptions = [] ) {
		// Set options and fill in defaults
		$options = $setOptions + [
			'id' => 'footer',
			'class' => 'mw-footer',
			'order' => 'iconsfirst',
			'link-prefix' => 'footer',
			'link-style' => null
		];

		// workaround to remove empty subarrays of footer icons (i.e. missing copyright)
		// that cause notice in MW 1.32
		$validFooterIcons = array_filter( array_map( 'array_filter', $this->get( 'footericons' ) ) );
		$validFooterLinks = $this->getFooterLinks( $options['link-style'] );

		$html = '';

		$html .= $this->htmlClass::openElement( 'div', [
			'id' => $options['id'],
			'class' => $options['class'],
			'role' => 'contentinfo',
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' )
		] );

		$iconsHTML = '';
		if ( count( $validFooterIcons ) > 0 ) {
			$iconsHTML .= $this->htmlClass::openElement( 'ul', [ 'id' => "{$options['link-prefix']}-icons" ] );
			foreach ( $validFooterIcons as $blockName => $footerIcons ) {
				$iconsHTML .= $this->htmlClass::openElement( 'li', [
					'id' => Sanitizer::escapeIdForAttribute(
						"{$options['link-prefix']}-{$blockName}ico"
					),
					'class' => 'footer-icons'
				] );
				foreach ( $footerIcons as $iconkey => $icon ) {
					$iconsHTML .= $this->getSkin()->makeFooterIcon( $icon );
				}
				$iconsHTML .= $this->htmlClass::closeElement( 'li' );
			}
			$iconsHTML .= $this->htmlClass::closeElement( 'ul' );
		}

		$linksHTML = '';
		if ( count( $validFooterLinks ) > 0 ) {
			if ( $options['link-style'] == 'flat' ) {
				$linksHTML .= $this->htmlClass::openElement( 'ul', [
					'id' => "{$options['link-prefix']}-list",
					'class' => 'footer-places'
				] );
				foreach ( $validFooterLinks as $link ) {
					$linksHTML .= $this->htmlClass::rawElement(
						'li',
						[ 'id' => Sanitizer::escapeIdForAttribute( $link ) ],
						$this->get( $link )
					);
				}
				$linksHTML .= $this->htmlClass::closeElement( 'ul' );
			} else {
				$linksHTML .= $this->htmlClass::openElement( 'div', [ 'id' => "{$options['link-prefix']}-list" ] );
				foreach ( $validFooterLinks as $category => $links ) {
					$linksHTML .= $this->htmlClass::openElement( 'ul',
						[ 'id' => Sanitizer::escapeIdForAttribute(
							"{$options['link-prefix']}-{$category}"
						) ]
					);
					foreach ( $links as $link ) {
						$linksHTML .= $this->htmlClass::rawElement(
							'li',
							[ 'id' => Sanitizer::escapeIdForAttribute(
								"{$options['link-prefix']}-{$category}-{$link}"
							) ],
							$this->get( $link )
						);
					}
					$linksHTML .= $this->htmlClass::closeElement( 'ul' );
				}
				$linksHTML .= $this->htmlClass::closeElement( 'div' );
			}
		}

		if ( $options['order'] == 'iconsfirst' ) {
			$html .= $iconsHTML . $linksHTML;
		} else {
			$html .= $linksHTML . $iconsHTML;
		}

		$html .= $this->getClear() . $this->htmlClass::closeElement( 'div' );

		return $html;
	}
}
