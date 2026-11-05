<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

/**
 * Every test starts from the demo data: the schema made anew and the fixtures loaded (the tasks file too).
 */
abstract class PanelTestCase extends WebTestCase
{
    use InteractsWithLiveComponents;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Request::create() asks for English; the demo speaks Polish first.
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'pl');
        $entities = $this->entities();
        $schema = new SchemaTool($entities);
        $metadata = $entities->getMetadataFactory()->getAllMetadata();
        // The demo database is SQLite with its foreign keys checked: they would stop the tables from going one by one.
        $entities->getConnection()->executeStatement('PRAGMA foreign_keys = OFF');
        $schema->dropDatabase();
        $entities->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
        $schema->createSchema($metadata);
        new ORMExecutor($entities, new ORMPurger($entities))->execute([static::getContainer()->get(AppFixtures::class)]);
    }

    /**
     * Submits the form of a live component that is expected to refuse it: the component answers 422 (with the
     * errors in the form), which the test client throws.
     *
     * @param array<string, mixed> $values
     */
    protected static function submitInvalid(TestLiveComponent $component, array $values): string
    {
        try {
            $component->submitForm($values, 'save');
        } catch (UnprocessableEntityHttpException $exception) {
            return $exception->getMessage();
        }
        self::fail('The form was accepted.');
    }

    protected function entities(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** The demo accounts of config/packages/security.yaml, as their provider gives them. */
    protected static function admin(): UserInterface
    {
        return self::account('admin');
    }

    protected static function user(): UserInterface
    {
        return self::account('user');
    }

    private static function account(string $name): UserInterface
    {
        return static::getContainer()->get('security.user.provider.concrete.users_in_memory')->loadUserByIdentifier($name);
    }
}
