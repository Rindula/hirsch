<?php

namespace App\Tests\Controller;

use App\Entity\Holidays;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity\Client;
use Symfony\Component\HttpFoundation\Response;

class ModalControllerTest extends WebTestCase
{
    private EntityManager $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->entityManager->beginTransaction();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testInHolidays(){
        $holiday = new Holidays();
        $holiday->setStart(new \DateTime("-1 day"));
        $holiday->setEnd(new \DateTime("+1 day"));
        $this->entityManager->persist($holiday);
        $this->entityManager->flush();

        $this->client->request('GET','/modalInformationText');
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($this->client->getResponse()->getContent());

    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testNotInHolidays(){
        $holiday = new Holidays();
        $holiday->setStart(new \DateTime("+1 day"));
        $holiday->setEnd(new \DateTime("+3 day"));
        $this->entityManager->persist($holiday);
        $this->entityManager->flush();

        $this->client->request('GET','/modalInformationText');
        $this->assertResponseIsSuccessful();
        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }
}
