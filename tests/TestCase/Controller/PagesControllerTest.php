<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * PagesControllerTest class
 *
 * @uses \App\Controller\PagesController
 */
class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected $fixtures = [
        "app.Hirsch",
        "app.Holidays",
        "app.Orders",
        "app.Paypalmes",
        "app.Payhistory",
    ];

    /**
     * testMultipleGet method
     *
     * @return void
     */
    public function testMultipleGet()
    {
        Configure::write("debug", false);
        $this->get('/');
        $this->assertResponseCode(302);
        $this->assertRedirect("/karte");
        $this->get('/karte');
        $this->assertResponseOk();
        $this->assertStringContainsString("<html", $this->_getBodyAsString());
        $this->get('/bestellungen/');
        $this->assertResponseOk();
        $this->assertStringContainsString("<html", $this->_getBodyAsString());
        $this->get('/zahlen-bitte/');
        $this->assertResponseOk();
        $this->assertStringContainsString("<html", $this->_getBodyAsString());
        $this->get('/bestellen/0/tagesessen');
        $this->assertResponseSuccess();
        Configure::write("debug", true);
        $this->get('/bestellen/0/tagesessen');
        $this->assertResponseOk();
        $this->assertStringContainsString("<html", $this->_getBodyAsString());
    }

    /**
     * testDisplay method
     *
     * @return void
     */
    public function testDisplay()
    {
        $this->get('/pages/home');
        $this->assertRedirect("/karte");
    }

    /**
     * Test that missing template renders 404 page in production
     *
     * @return void
     */
    public function testMissingTemplate()
    {
        Configure::write('debug', false);
        $this->get('/pages/not_existing');

        $this->assertResponseError();
        $this->assertResponseContains('Error');
    }

    /**
     * Test that missing template in debug mode renders missing_template error page
     *
     * @return void
     */
    public function testMissingTemplateInDebug()
    {
        Configure::write('debug', true);
        $this->get('/pages/not_existing');

        $this->assertResponseFailure();
        $this->assertResponseContains('Missing Template');
        $this->assertResponseContains('Stacktrace');
        $this->assertResponseContains('not_existing.php');
    }

    /**
     * Test directory traversal protection
     *
     * @return void
     */
    public function testDirectoryTraversalProtection()
    {
        $this->get('/pages/../Layout/ajax');
        $this->assertResponseCode(403);
        $this->assertResponseContains('Forbidden');
    }

    /**
     * Test that CSRF protection is applied to page rendering.
     *
     * @return void
     */
    public function testCsrfAppliedError()
    {
        $this->markTestSkipped("CSRF disabled");
        Configure::write("debug", true);
        $this->post('/', ['hello' => 'world']);

        $this->assertResponseCode(403);
        $this->assertResponseContains('CSRF');
    }

    /**
     * Test that CSRF protection is applied to page rendering.
     *
     * @return void
     */
    public function testCsrfAppliedOk()
    {
        $this->markTestSkipped("CSRF disabled");
        Configure::write("debug", true);
        $this->enableCsrfToken();
        $this->post('/');
        $this->assertResponseCode(200);
    }
}
