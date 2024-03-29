<?php

/*
 * (c) Sven Nolting, 2023
 */

namespace App\Tests\Controller;

use App\Controller\OrderController;
use App\Entity\Hirsch;
use App\Entity\Orders;
use App\Entity\Payhistory;
use App\Entity\Paypalmes;
use App\Repository\PaypalmesRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * @group time-sensitive
 */
class OrderTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ClockMock::register(OrderController::class);
        // clear cache
        $cache = new FilesystemAdapter();
        $cache->clear();
    }

    private function loggedInClient(): KernelBrowser
    {
        $client = static::createClient([], ['REMOTE_ADDR' => '1.1.1.1']);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneByUsername('test');
        if (null === $user) {
            $this->fail('No user found with username "test"');
        }
        $client->loginUser($user);

        return $client;
    }

    public function testOrderingAuthenticatedTooLate(): void
    {
        // Test with no active payer
        ClockMock::withClockMock(strtotime('12:00'));
        $client = $this->loggedInClient();
        $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseRedirects('/karte', 302);

        ClockMock::withClockMock(strtotime('10:54'));
        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        ClockMock::withClockMock(strtotime('10:55:59'));
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '+ Pommes', ]);
        $this->assertResponseRedirects('/zahlen-bitte/', 302);

        ClockMock::withClockMock(strtotime('10:54'));
        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        ClockMock::withClockMock(strtotime('11:00:05'));
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '+ Pommes', ]);
        $this->assertResponseRedirects('/karte', 302);
        $client->followRedirect();
        $this->assertStringContainsString('Bitte such dir eine Alternative', $client->getResponse()->getContent() ?: '');

        // Test with active payer

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        /** @var Paypalmes $paypalme */
        $paypalme = $entityManager->getRepository(Paypalmes::class)->findOneBy([]);

        $ph = new Payhistory();

        $ph->setCreated(new DateTime());
        $ph->setPaypalme($paypalme);

        $entityManager->getRepository(Payhistory::class)->add($ph);

        ClockMock::withClockMock(strtotime('12:00'));
        $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        self::assertResponseRedirects('/karte', 302);

        ClockMock::withClockMock(strtotime('10:54'));
        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        ClockMock::withClockMock(strtotime('10:55:59'));
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '+ Pommes', ]);
        self::assertResponseRedirects('/zahlen-bitte/', 302);

        ClockMock::withClockMock(strtotime('10:54'));
        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        ClockMock::withClockMock(strtotime('11:00:05'));
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '+ Pommes', ]);
        $this->assertResponseRedirects('/karte', 302);
        $client->followRedirect();
        $this->assertStringContainsString('Bitte such dir eine Alternative', $client->getResponse()->getContent() ?: '');

        ClockMock::withClockMock(false);
    }

    public function testOrderingAuthenticatedInTime(): void
    {
        ClockMock::withClockMock(strtotime('08:00'));
        $client = $this->loggedInClient();
        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '', ]);
        $this->assertResponseRedirects('/zahlen-bitte/', 302);

        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '+ Pommes', ]);
        $this->assertResponseRedirects('/zahlen-bitte/', 302);

        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        $client->submit($form, [
            'order[orderedby]' => '',
            'order[note]' => '', ]);
        $this->assertResponseStatusCodeSame(500);

        $crawler = $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        $client->submit($form, [
            'order[orderedby]' => '',
            'order[note]' => '+ Pommes', ]);
        $this->assertResponseStatusCodeSame(500);
    }

    public function testPreordering(): void
    {
        ClockMock::withClockMock(strtotime('11:10'));
        $client = $this->loggedInClient();
        $crawler = $client->request('GET', '/order/1/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Schweizer Wurstsalat mit Pommes');
        $form = $crawler->selectButton('order[submit]')->form();
        $client->submit($form, [
            'order[orderedby]' => 'Max Mustermann',
            'order[note]' => '', ]);
        $this->assertResponseRedirects('/zahlen-bitte/', 302);
    }

    public function testOrderingUnauthenticated(): void
    {
        $client = static::createClient([], ['REMOTE_ADDR' => '1.1.1.1']);

        $client->request('GET', '/order/0/Schweizer-Wurstsalat-mit-Pommes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testOrderingInvalidOrderSlug(): void
    {
        $client = $this->loggedInClient();
        $client->request('GET', '/order/0/sngerjuioernruioesbnioernb');
        $this->assertResponseRedirects('/karte', 302);
    }

    public function testOrderUntil(): void
    {
        $client = $this->loggedInClient();
        $client->request('GET', '/order-until');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('', 'Die Bestellung ist ab 10:55 nicht mehr möglich.');
    }

    public function testOrdersOverview(): void
    {
        $client = $this->loggedInClient();
        $client->request('GET', '/bestellungen/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Heutige Bestellungen');
    }

    public function testPayNowPage(): void
    {
        $client = $this->loggedInClient();

        $payPalMesRepository = static::getContainer()->get(PaypalmesRepository::class);
        $paypalmeId = $payPalMesRepository->findOneBy([])->getId();

        $crawler = $client->request('GET', '/zahlen-bitte/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paypalierer');
        $form = $crawler->selectButton('id')->form();
        $client->submit($form, [
            'id' => $paypalmeId, ]);
        $this->assertResponseRedirects('https://paypal.me/rindulalp/4', 302);

        $crawler = $client->request('GET', '/zahlen-bitte/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paypalierer');
        $this->assertSelectorExists('.paypalmeslistitem.active');
        $this->assertSelectorTextContains('.paypalmeslistitem.active', 'Sven Nolting');
        $form = $crawler->selectButton('id')->form();
        $client->submit($form, [
            'id' => $paypalmeId,
            'tip' => '0',
        ]);
        $this->assertResponseRedirects('https://paypal.me/rindulalp/3.5', 302);

        $crawler = $client->request('GET', '/zahlen-bitte/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paypalierer');
        $form = $crawler->selectButton('id')->form();
        $client->submit($form, [
            'id' => $paypalmeId,
            'tip' => '0.5',
        ]);
        $this->assertResponseRedirects('https://paypal.me/rindulalp/4', 302);

        $crawler = $client->request('GET', '/zahlen-bitte/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paypalierer');
        $form = $crawler->selectButton('id')->form();
        $client->submit($form, [
            'id' => $paypalmeId,
            'tip' => '-1',
        ]);
        $this->assertResponseRedirects('https://paypal.me/rindulalp/3.5', 302);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testOrderDelete(): void
    {
        $client = $this->loggedInClient();

        $order = $this->createHirschOrder();

        ClockMock::withClockMock(strtotime('10:54'));
        $client->request('GET', '/orders/delete/'.$order->getId());
        $this->assertResponseRedirects('/bestellungen/', 302);
        $client->followRedirect();
        $this->assertStringContainsString('Bestellung gelöscht', $client->getResponse()->getContent() ?: '');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testOrderDeleteAfterOrderMailSend(): void
    {
        $client = $this->loggedInClient();
        $order = $this->createHirschOrder();

        ClockMock::withClockMock(strtotime('11:01'));
        $client->request('GET', '/orders/delete/'.$order->getId());
        $this->assertResponseRedirects('/karte', 302);
        $client->followRedirect();
        $this->assertStringContainsString('Der Besteller ist bereits informiert. Die Bestellung kann nicht mehr gelöscht werden!',
            $client->getResponse()->getContent() ?: '');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testApiGetAllOrders(): void
    {
        $client = $this->loggedInClient();

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $testOrderUser = 'Testuser';

        $today = new DateTime('today 9:00');
        $yesterday = new DateTime('yesterday 9:00');

        /** @var Hirsch $tagesessen */
        $tagesessen = $entityManager->getRepository(Hirsch::class)->find(1);

        $todayOrder = new Orders();
        $todayOrder->setForDate($today);
        $todayOrder->setCreated($today);
        $todayOrder->setHirsch($tagesessen);
        $todayOrder->setOrderedby($testOrderUser);

        $yesterdayOrder = new Orders();
        $yesterdayOrder->setForDate($yesterday);
        $yesterdayOrder->setCreated($yesterday);
        $yesterdayOrder->setHirsch($tagesessen);
        $yesterdayOrder->setOrderedby($testOrderUser);

        $entityManager->persist($todayOrder);
        $entityManager->persist($yesterdayOrder);
        $entityManager->flush();

        $client->request('GET', '/api/orders/0');
        $this->verifyOrdersApi($client, 2, [0 => $todayOrder]);

        $client->request('GET', '/api/orders');
        $this->verifyOrdersApi($client, 1, [0 => $todayOrder]);

        $client->request('GET', '/api/orders/1');
        $this->verifyOrdersApi($client, 1, [0 => $todayOrder]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param array<int,Orders> $testOrdersArray
     *
     * @throws \Exception
     */
    private function verifyOrdersApi(KernelBrowser $client, int $expectedCount, array $testOrdersArray): void
    {
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse()->getContent();
        if (!$response) {
            $this->fail('Not a valid response');
        }
        $response = json_decode($response);
        if (!is_array($response)) {
            $this->fail('Not a valid response');
        }
        $this->assertCount($expectedCount, $response);
        foreach ($testOrdersArray as $key => $order) {
            $orderToTest = $response[$key];
            $meal = $order->getHirsch();
            if (null === $meal) {
                $this->fail('Did expect something to eat');
            }
            $this->assertEquals($order->getId(), $orderToTest->id);
            $this->assertEquals($order->getCreated(), new DateTime($orderToTest->created->date));
            $forDate = $order->getForDate();
            if (null === $forDate) {
                $this->fail('Did expect a order for date');
            }
            $this->assertEquals($forDate->format('Y-m-d'), (new DateTime($orderToTest->forDate->date))->format('Y-m-d'));
            $this->assertEquals($order->getNote(), $orderToTest->note);
            $this->assertEquals($order->getOrderedby(), $orderToTest->orderedby);
            $this->assertEquals($meal->getName(), $orderToTest->ordered);
            $this->assertEquals($meal->getSlug(), $orderToTest->orderedSlug);
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createHirschOrder(): Orders
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();

        /** @var Hirsch $tagesessen */
        $tagesessen = $entityManager->getRepository(Hirsch::class)->find(1);

        $order = new Orders();
        $order->setCreated(new \DateTime('now'));
        $order->setForDate(new \DateTime('now'));
        $order->setHirsch($tagesessen);
        $order->setOrderedby('Max Mustermann');

        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }
}
