<?php

class WarehouseServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetWarehouse()
    {
        $dbConnectionMock = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbConnectionMock->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM warehouses WHERE id = ?',
                [123])
            ->will($this->returnValue(['asfafjhkf']));

        $warehouseService = new \App\Services\WarehouseService($dbConnectionMock);
        $warehouseService->GetWarehouse();
    }
}