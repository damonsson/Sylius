<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Sylius\Behat\Page\Shop\Product\IndexPageInterface;
use Sylius\Behat\Page\Shop\Product\ShowPageInterface;
use Sylius\Behat\Page\SymfonyPageInterface;
use Sylius\Behat\Service\Setter\ChannelContextSetterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Webmozart\Assert\Assert;

/**
 * @author Kamil Kokot <kamil.kokot@lakion.com>
 * @author Magdalena Banasiak <magdalena.banasiak@lakion.com>
 * @author Anna Walasek <anna.walasek@lakion.com>
 */
final class ProductContext implements Context
{
    /**
     * @var ShowPageInterface
     */
    private $showPage;

    /**
     * @var IndexPageInterface
     */
    private $indexPage;

    /**
     * @var ChannelContextSetterInterface
     */
    private $channelContextSetter;

    /**
     * @var SymfonyPageInterface
     */
    private $taxonShowPage;

    /**
     * @param ShowPageInterface $showPage
     * @param IndexPageInterface $indexPage
     * @param SymfonyPageInterface $taxonShowPage
     * @param ChannelContextSetterInterface $channelContextSetter
     */
    public function __construct(
        ShowPageInterface $showPage,
        IndexPageInterface $indexPage,
        SymfonyPageInterface $taxonShowPage,
        ChannelContextSetterInterface $channelContextSetter
    ) {
        $this->showPage = $showPage;
        $this->indexPage = $indexPage;
        $this->taxonShowPage = $taxonShowPage;
        $this->channelContextSetter = $channelContextSetter;
    }

    /**
     * @Then I should be able to access product :product
     */
    public function iShouldBeAbleToAccessProduct(ProductInterface $product)
    {
        $this->showPage->tryToOpen(['slug' => $product->getSlug()]);

        Assert::true(
            $this->showPage->isOpen(['slug' => $product->getSlug()]),
            'Product show page should be open, but it does not.'
        );
    }

    /**
     * @Then I should not be able to access product :product
     */
    public function iShouldNotBeAbleToAccessProduct(ProductInterface $product)
    {
        $this->showPage->tryToOpen(['slug' => $product->getSlug()]);

        Assert::false(
            $this->showPage->isOpen(['slug' => $product->getSlug()]),
            'Product show page should not be open, but it does.'
        );
    }

    /**
     * @When /^I check (this product)'s details/
     */
    public function iOpenProductPage(ProductInterface $product)
    {
        $this->showPage->open(['slug' => $product->getSlug()]);
    }

    /**
     * @Given I should see the product name :name
     */
    public function iShouldSeeProductName($name)
    {
        Assert::same(
            $name,
            $this->showPage->getName(),
            'Product should have name %2$s, but it has %s'
        );
    }

    /**
     * @When I open page :url
     */
    public function iOpenPage($url)
    {
        $this->showPage->visit($url);
    }

    /**
     * @Then I should be on :product product detailed page
     */
    public function iShouldBeOnProductDetailedPage(ProductInterface $product)
    {
        Assert::true(
            $this->showPage->isOpen(['slug' => $product->getSlug()]),
            sprintf('Product %s show page should be open, but it does not.', $product->getName())
        );
    }

    /**
     * @Then I should see the product attribute :attributeName with value :AttributeValue
     */
    public function iShouldSeeTheProductAttributeWithValue($attributeName, $AttributeValue)
    {
        Assert::true(
            $this->showPage->isAttributeWithValueOnPage($attributeName, $AttributeValue),
            sprintf('Product should have attribute %s with value %s, but it does not.', $attributeName, $AttributeValue)
        );
    }

    /**
     * @Given /^I want to see products in (channel "([^"]*)")$/
     */
    public function iWantToSeeProductsInChannel(ChannelInterface $channel)
    {
        $this->channelContextSetter->setChannel($channel);
    }

    /**
     * @When /^I check list of products for (taxon "([^"]+)")$/
     */
    public function iCheckListOfProductsForTaxon(TaxonInterface $taxon)
    {
        $this->taxonShowPage->open(['permalink' => $taxon->getPermalink()]);
    }

    /**
     * @Then I should see the product :productName
     */
    public function iShouldSeeProduct($productName)
    {
        Assert::true(
            $this->indexPage->isResourceOnPage($productName),
            sprintf("The product %s should appear on page, but it does not.", $productName)
        );
    }

    /**
     * @Then I should not see the product :productName
     */
    public function iShouldNotSeeProduct($productName)
    {
        Assert::false(
            $this->indexPage->isResourceOnPage($productName),
            sprintf("The product %s should not appear on page, but it does.", $productName)
        );
    }

    /**
     * @Then I should see information about empty list of products
     */
    public function iShouldSeeInformationAboutEmptyListOfProducts()
    {
        Assert::true(
            $this->indexPage->isEmpty(),
            'There should appear information about empty list of products, but it does not.'
        );
    }
}
