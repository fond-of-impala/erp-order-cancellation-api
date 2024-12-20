<?php

namespace FondOfImpala\Zed\ErpOrderCancellationApi;

use Codeception\Test\Unit;
use Exception;
use FondOfImpala\Zed\ErpOrderCancellation\Business\ErpOrderCancellationFacadeInterface;
use FondOfImpala\Zed\ErpOrderCancellationApi\Dependency\Facade\ErpOrderCancellationApiToApiFacadeBridge;
use FondOfImpala\Zed\ErpOrderCancellationApi\Dependency\Facade\ErpOrderCancellationApiToErpOrderCancellationFacadeBridge;
use FondOfImpala\Zed\ErpOrderCancellationApi\Dependency\QueryContainer\ErpOrderCancellationApiToApiQueryBuilderQueryContainerBridge;
use Orm\Zed\ErpOrderCancellation\Persistence\FoiErpOrderCancellationQuery;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Shared\Kernel\BundleProxy;
use Spryker\Zed\Api\Business\ApiFacadeInterface;
use Spryker\Zed\ApiQueryBuilder\Persistence\ApiQueryBuilderQueryContainerInterface;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Kernel\Locator;

class ErpOrderCancellationApiDependencyProviderTest extends Unit
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ApiQueryBuilder\Persistence\ApiQueryBuilderQueryContainerInterface
     */
    protected MockObject|ApiQueryBuilderQueryContainerInterface $apiQueryBuilderQueryContainerMock;

    /**
     * @var \FondOfImpala\Zed\ErpOrderCancellationApi\ErpOrderCancellationApiDependencyProvider
     */
    protected ErpOrderCancellationApiDependencyProvider $dependencyProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Kernel\Container
     */
    protected MockObject|Container $containerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\Kernel\BundleProxy
     */
    protected MockObject|BundleProxy $bundleProxyMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Api\Business\ApiFacadeInterface
     */
    protected MockObject|ApiFacadeInterface $apiFacadeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\FondOfImpala\Zed\ErpOrderCancellation\Business\ErpOrderCancellationFacadeInterface
     */
    protected MockObject|ErpOrderCancellationFacadeInterface $erpOrderCancellationFacadeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Kernel\Locator
     */
    protected MockObject|Locator $locatorMock;

    /**
     * @return void
     */
    protected function _before(): void
    {
        $containerMock = $this->getMockBuilder(Container::class);

        /** @phpstan-ignore-next-line */
        if (method_exists($containerMock, 'setMethodsExcept')) {
            /** @phpstan-ignore-next-line */
            $containerMock->setMethodsExcept(['factory', 'set', 'offsetSet', 'get', 'offsetGet']);
        } else {
            /** @phpstan-ignore-next-line */
            $containerMock->onlyMethods(['getLocator'])->enableOriginalClone();
        }

        $this->containerMock = $containerMock->getMock();

        $this->locatorMock = $this->getMockBuilder(Locator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->bundleProxyMock = $this->getMockBuilder(BundleProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->erpOrderCancellationFacadeMock = $this
            ->getMockBuilder(ErpOrderCancellationFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiFacadeMock = $this->getMockBuilder(ApiFacadeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiQueryBuilderQueryContainerMock = $this
            ->getMockBuilder(ApiQueryBuilderQueryContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dependencyProvider = new ErpOrderCancellationApiDependencyProvider();
    }

    /**
     * @return void
     */
    public function testProvideBusinessLayerDependencies(): void
    {
        $self = $this;
        $this->containerMock->expects(static::atLeastOnce())
            ->method('getLocator')
            ->willReturn($this->locatorMock);

        $this->locatorMock->expects(static::atLeastOnce())
            ->method('__call')
            ->willReturnCallback(static function (string $key) use ($self) {
                switch ($key) {
                    case 'erpOrderCancellation':
                        return $self->bundleProxyMock;
                    case 'api':
                        return $self->bundleProxyMock;
                }

                throw new Exception('Invalid key');
            });

        $this->bundleProxyMock->expects(static::atLeastOnce())
            ->method('__call')
            ->with('facade')
            ->willReturnOnConsecutiveCalls(
                $this->erpOrderCancellationFacadeMock,
                $this->apiFacadeMock,
            );

        $container = $this->dependencyProvider
            ->provideBusinessLayerDependencies($this->containerMock);

        static::assertEquals($this->containerMock, $container);

        self::assertInstanceOf(
            ErpOrderCancellationApiToErpOrderCancellationFacadeBridge::class,
            $container[ErpOrderCancellationApiDependencyProvider::FACADE_ERP_ORDER_CANCELLATION],
        );

        self::assertInstanceOf(
            ErpOrderCancellationApiToApiFacadeBridge::class,
            $container[ErpOrderCancellationApiDependencyProvider::FACADE_API],
        );
    }

    /**
     * @return void
     */
    public function testProvidePersistenceLayerDependencies(): void
    {
        $this->containerMock->expects(static::atLeastOnce())
            ->method('getLocator')
            ->willReturn($this->locatorMock);

        $this->locatorMock->expects(static::atLeastOnce())
            ->method('__call')
            ->with('apiQueryBuilder')
            ->willReturn($this->bundleProxyMock);

        $this->bundleProxyMock->expects(static::atLeastOnce())
            ->method('__call')
            ->with('queryContainer')
            ->willReturnOnConsecutiveCalls($this->apiQueryBuilderQueryContainerMock);

        $container = $this->dependencyProvider
            ->providePersistenceLayerDependencies($this->containerMock);

        static::assertEquals($this->containerMock, $container);

        self::assertInstanceOf(
            FoiErpOrderCancellationQuery::class,
            $container[ErpOrderCancellationApiDependencyProvider::PROPEL_QUERY_ERP_ORDER_CANCELLATION],
        );

        self::assertInstanceOf(
            ErpOrderCancellationApiToApiQueryBuilderQueryContainerBridge::class,
            $container[ErpOrderCancellationApiDependencyProvider::QUERY_CONTAINER_API_QUERY_BUILDER],
        );
    }
}
