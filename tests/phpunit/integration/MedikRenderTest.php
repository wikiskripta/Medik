<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Request\FauxRequest;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

/**
 * Renders the real skin, so that any MediaWiki deprecation hit while rendering fails the suite:
 * MediaWiki's PHPUnit configuration turns deprecations into errors.
 *
 * @covers \SkinMedik
 * @covers \MedikTemplate
 *
 * @group skins-medik
 * @group Database
 */
class MedikRenderTest extends MediaWikiIntegrationTestCase {

	public function testAnonymousPageContainsTheLoginLink(): void {
		$this->assertStringContainsString(
			'pt-login',
			$this->renderPage( Title::newFromText( 'Main Page' ) )
		);
	}

	public function testPageContainsTheNamespaceViewAndActionTabs(): void {
		$html = $this->renderPage(
			$this->getExistingTestPage( 'Medik render test' )->getTitle(),
			$this->getTestSysop()->getUser()
		);

		$this->assertStringContainsString( 'ca-nstab-main', $html );
		$this->assertStringContainsString( 'ca-history', $html );
		$this->assertStringContainsString( 'ca-move', $html );
	}

	public function testSpecialPageContainsItsNavigationLinks(): void {
		$this->assertStringContainsString(
			'Special:EditWatchlist/clear',
			$this->renderSpecialPage( 'EditWatchlist', $this->getTestUser()->getUser() )
		);
	}

	private function renderPage( Title $title, ?User $user = null ): string {
		return $this->newContext( $title, $user )->getSkin()->generateHTML();
	}

	private function renderSpecialPage( string $name, User $user ): string {
		$context = $this->newContext( SpecialPage::getTitleFor( $name ), $user );

		$this->getServiceContainer()->getSpecialPageFactory()->executePath( $name, $context );

		return $context->getSkin()->generateHTML();
	}

	private function newContext( Title $title, ?User $user ): RequestContext {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setTitle( $title );

		if ( $user !== null ) {
			$context->setUser( $user );
		}

		$context->setSkin( $this->getServiceContainer()->getSkinFactory()->makeSkin( 'medik' ) );

		return $context;
	}

}
