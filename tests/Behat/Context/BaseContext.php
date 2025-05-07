<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use Behat\MinkExtension\Context\MinkContext;
use Doctrine\ORM\EntityManagerInterface;

class BaseContext extends MinkContext
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @Then I should be redirected to the :path page
     */
    public function iShouldBeRedirectedToThePage(string $path): void
    {
        $this->assertSession()->addressEquals($this->locatePath($path));
    }

    /**
     * @Then I should still be on the :path page
     */
    public function iShouldStillBeOnThePage(string $path): void
    {
        $this->assertSession()->addressEquals($this->locatePath($path));
    }

    /**
     * @Then I should see :text
     */
    public function iShouldSee(string $text): void
    {
        $this->assertSession()->pageTextContains($text);
    }

    /**
     * @Then I should see the error message :message
     */
    public function iShouldSeeTheErrorMessage(string $message): void
    {
        $this->assertSession()->elementTextContains('css', '.alert-danger', $message);
    }
} 