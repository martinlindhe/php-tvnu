<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    var $output = [];
    var $returnValue = 0;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I am in the terminal
     */
    public function iAmInTheTerminal()
    {
        if (PHP_SAPI != 'cli') {
            throw new PendingException('not in cli');
        }
    }

    /**
     * @Given the terminal width is at least :arg1 characters
     */
    public function theTerminalWidthIsAtLeastCharacters($arg1)
    {
        $width = intval(exec('tput cols'));
        if ($width < $arg1) {
            throw new PendingException('currently '.$width.' characters');
        }
    }

    /**
     * @Given the terminal width is less than :arg1 characters
     */
    public function theTerminalWidthIsLessThanCharacters($arg1)
    {
        $width = intval(exec('tput cols'));
        if ($width >= $arg1) {
            throw new PendingException('currently '.$width.' characters');
        }
    }

    /**
     * @When I run :command
     */
    public function iRun($command)
    {
        exec($command, $this->output, $this->returnValue);
    }

    /**
     * @Then I should get :arg1 columns
     */
    public function iShouldGetColumns($columns)
    {
        if (!$this->output) {
            throw new \Exception('Got no result');
        }
        return substr_count($this->output[0], '==>') == $columns;
    }

    /**
     * @Then I should get at least :arg1 rows
     */
    public function iShouldGetAtLeastRows($arg1)
    {
        if (count($this->output) < $arg1) {
            throw new \Exception('Only got '.count($this->output).' rows');
        }
    }

    /**
     * @Then I should get a search result
     */
    public function iShouldGetASearchResult()
    {
        if (!$this->output) {
            throw new \Exception('Got no result');
        }

        if (count($this->output) < 2 || strpos($this->output[0], 'Matching') === false) {
            throw new \Exception('Unexpected result');
        }
    }

    /**
     * @Then I should get a program listing containing :arg1
     */
    public function iShouldGetAProgramListingContaining($arg1)
    {
        if (!$this->output) {
            throw new \Exception('Got no result');
        }

        if (strpos($this->output[0], $arg1) === false) {
            throw new \Exception('Unexpected result');
        }
    }

    /**
     * @Then I should get a help screen
     */
    public function iShouldGetAHelpScreen()
    {
        if (!$this->output) {
            throw new \Exception('Got no result');
        }

        if (count($this->output) < 2 || strpos($this->output[0], 'Usage') === false) {
            throw new \Exception('Unexpected result');
        }
    }

    /**
     * @Then I should get a error message
     */
    public function iShouldGetAErrorMessage()
    {
        if (!$this->output) {
            throw new \Exception('Got no result');
        }

        if (strpos($this->output[0], 'Error') === false) {
            throw new \Exception('Unexpected result');
        }
    }

    /**
     * @Then I should get error code :arg1
     */
    public function iShouldGetErrorCode($arg1)
    {
        if ($this->returnValue != $arg1) {
            throw new \Exception('Unexpected return code '.$this->returnValue);
        }
    }
}
