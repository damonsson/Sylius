<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Behat\Context\Api\Admin;

use ApiPlatform\Core\Api\IriConverterInterface;
use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Promotion\Event\CatalogPromotionUpdated;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class ManagingCatalogPromotionsContext implements Context
{
    private ApiClientInterface $client;
    private ResponseCheckerInterface $responseChecker;
    private MessageBusInterface $messageBus;
    private IriConverterInterface $iriConverter;
    private SharedStorageInterface $sharedStorage;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker,
        MessageBusInterface $messageBus,
        IriConverterInterface $iriConverter,
        SharedStorageInterface $sharedStorage
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
        $this->messageBus = $messageBus;
        $this->iriConverter = $iriConverter;
        $this->sharedStorage = $sharedStorage;
    }

    /**
     * @When I create a new catalog promotion with :code code and :name name
     */
    public function iCreateANewCatalogPromotionWithCodeAndName(string $code, string $name): void
    {
        $this->client->buildCreateRequest();
        $this->client->addRequestData('code', $code);
        $this->client->addRequestData('name', $name);
        $this->client->create();
    }

    /**
     * @When I create a new catalog promotion without specifying its code and name
     */
    public function iCreateANewCatalogPromotionWithoutSpecifyingItsCodeAndName(): void
    {
        $this->client->buildCreateRequest();
        $this->client->create();
    }

    /**
     * @When I want to create a new catalog promotion
     */
    public function iWantToCreateANewCatalogPromotion(): void
    {
        $this->client->buildCreateRequest();
    }

    /**
     * @When I specify its :field as :value
     * @When I :field it :value
     */
    public function iSpecifyItsAs(string $field, string $value): void
    {
        $this->client->addRequestData($field, $value);
    }

    /**
     * @When I specify its :field as :value in :localeCode
     */
    public function iSpecifyItsAsIn(string $field, string $value, string $localeCode): void
    {
        $data = ['translations' => [$localeCode => ['locale' => $localeCode]]];
        $data['translations'][$localeCode][$field] = $value;

        $this->client->updateRequestData($data);
    }

    /**
     * @When I describe it as :description in :localeCode
     */
    public function iDescribeItAsIn(string $description, string $localeCode): void
    {
        $data = ['translations' => [$localeCode => ['locale' => $localeCode]]];
        $data['translations'][$localeCode]['description'] = $description;

        $this->client->updateRequestData($data);
    }

    /**
     * @When I make it available in channel :channel
     */
    public function iMakeItAvailableInChannel(ChannelInterface $channel): void
    {
        $this->client->addRequestData('channels', [$this->iriConverter->getIriFromItem($channel)]);
    }

    /**
     * @When /^I make (it) unavailable in (channel "[^"]+")$/
     */
    public function iMakeItUnavailableInChannel(CatalogPromotionInterface $catalogPromotion, ChannelInterface $channel): void
    {
        $channels = $this->responseChecker->getValue($this->client->show($catalogPromotion->getCode()), 'channels');

        foreach (array_keys($channels, $this->iriConverter->getIriFromItem($channel)) as $key) {
            unset($channels[$key]);
        }

        $this->client->addRequestData('channels', $channels);
    }

    /**
     * @When I add it
     */
    public function iAddIt(): void
    {
        $this->client->create();
    }

    /**
     * @When I rename the :catalogPromotion catalog promotion to :name
     */
    public function iRenameTheCatalogPromotionTo(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $this->client->updateRequestData(['name' => $name]);
        $this->client->update();
    }

    /**
     * @When I try to change the code of the :catalogPromotion catalog promotion to :code
     */
    public function iTryToChangeTheCodeOfTheCatalogPromotionTo(
        CatalogPromotionInterface $catalogPromotion,
        string $code
    ): void {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());
        $this->client->updateRequestData(['code' => $code]);
        $this->client->update();
    }

    /**
     * @When I want to modify a catalog promotion :catalogPromotion
     */
    public function iWantToModifyACatalogPromotion(CatalogPromotionInterface $catalogPromotion): void
    {
        $this->client->buildUpdateRequest($catalogPromotion->getCode());

        $this->sharedStorage->set('catalog_promotion', $catalogPromotion);
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->client->update();
    }

    /**
     * @Then there should be :amount new catalog promotion on the list
     * @Then there should be an empty list of catalog promotions
     */
    public function thereShouldBeNewCatalogPromotionOnTheList(int $amount = 0): void
    {
        Assert::count($this->responseChecker->getCollection($this->client->index()), $amount);
    }

    /**
     * @Then it should have :code code and :name name
     */
    public function itShouldHaveCodeAndName(string $code, string $name): void
    {
        Assert::true(
            $this->responseChecker->hasItemWithValues($this->client->index(), ['code' => $code, 'name' => $name]),
            sprintf('Cannot find catalog promotions with code "%s" and name "%s" in the list', $code, $name)
        );
    }

    /**
     * @Then this catalog promotion should be usable
     */
    public function thisCatalogPromotionShouldBeUsable(): void
    {
        Assert::isInstanceOf($this->messageBus->getDispatchedMessages()[0]['message'], CatalogPromotionUpdated::class);
    }

    /**
     * @Then the catalog promotion :catalogPromotion should be available in channel :channel
     * @Then /^(this catalog promotion) should be available in (channel "[^"]+")$/
     */
    public function itShouldBeAvailableInChannel(CatalogPromotionInterface $catalogPromotion, ChannelInterface $channel): void
    {
        Assert::true(
            $this->responseChecker->hasValueInCollection(
                $this->client->show($catalogPromotion->getCode()),
                'channels',
                $this->iriConverter->getIriFromItem($channel)
            ),
            sprintf('Catalog promotion is not assigned to %s channel', $channel->getName())
        );
    }

    /**
     * @Then /^(this catalog promotion) should not be available in (channel "[^"]+")$/
     */
    public function itShouldNotBeAvailableInChannel(
        CatalogPromotionInterface $catalogPromotion,
        ChannelInterface $channel
    ): void {
        Assert::false(
            $this->responseChecker->hasValueInCollection(
                $this->client->show($catalogPromotion->getCode()),
                'channels',
                $this->iriConverter->getIriFromItem($channel)
            ),
            sprintf('Catalog promotion is assigned to %s channel', $channel->getName())
        );
    }

    /**
     * @Then I should be notified that it has been successfully edited
     */
    public function iShouldBeNotifiedThatItHasBeenSuccessfullyEdited(): void
    {
        Assert::true(
            $this->responseChecker->isUpdateSuccessful($this->client->getLastResponse()),
            'Catalog promotion could not be edited'
        );
    }

    /**
     * @Then /^(this catalog promotion) name should be "([^"]+)"$/
     */
    public function thisCatalogPromotionNameShouldBe(CatalogPromotionInterface $catalogPromotion, string $name): void
    {
        $response = $this->client->show($catalogPromotion->getCode());

        Assert::true(
            $this->responseChecker->hasValue($response, 'name', $name),
            sprintf('Catalog promotion\'s name %s does not exist', $name)
        );
    }

    /**
     * @Then /^(this catalog promotion) (label|description) in ("[^"]+" locale) should be "([^"]+)"$/
     */
    public function thisCatalogPromotionLabelInLocaleShouldBe(
        CatalogPromotionInterface $catalogPromotion,
        string $field,
        string $localeCode,
        string $value
    ): void {
        $response = $this->client->show($catalogPromotion->getCode());

        Assert::true($this->responseChecker->hasTranslation($response, $localeCode, $field, $value));
    }

    /**
     * @Then I should be notified that code and name are required
     */
    public function iShouldBeNotifiedThatCodeAndNameAreRequired(): void
    {
        $validationError = $this->responseChecker->getError($this->client->getLastResponse());

        Assert::contains($validationError, 'code: Please enter catalog promotion code.');
        Assert::contains($validationError, 'name: Please enter catalog promotion name.');
    }

    /**
     * @Then I should be notified that catalog promotion with this code already exists
     */
    public function iShouldBeNotifiedThatCatalogPromotionWithThisCodeAlreadyExists(): void
    {
        $response = $this->client->getLastResponse();
        Assert::false(
            $this->responseChecker->isCreationSuccessful($response),
            'Catalog promotion has been created successfully, but it should not'
        );
        Assert::same(
            $this->responseChecker->getError($response),
            'code: The catalog promotion with given code already exists.'
        );
    }

    /**
     * @Then there should still be only one catalog promotion with code :code
     */
    public function thereShouldStillBeOnlyOneCatalogPromotionWithCode(string $code): void
    {
        Assert::count($this->responseChecker->getCollectionItemsWithValue($this->client->index(), 'code', $code), 1);
    }

    /**
     * @Then this catalog promotion code should still be :code
     */
    public function thisCatalogPromotionCodeShouldStillBe(string $code): void
    {
        Assert::true(
            $this->responseChecker->hasValue($this->client->getLastResponse(), 'code', $code),
            'The code has been changed, but it should not'
        );
    }
}