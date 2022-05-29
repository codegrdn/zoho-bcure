<?php


namespace App\Repositories;

use Exception;
use zcrmsdk\crm\api\response\BulkAPIResponse;
use zcrmsdk\crm\api\response\EntityResponse;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\setup\users\ZCRMUser;

/**
 * Class ZCRMHelper
 * @package App\Repositories
 */
class ZCRMHelper
{
    public const ZCRM_STATUS_SUCCESS = "success";
    public const ZCRM_STATUS_ERROR = "error";

    /**
     * @param  ZCRMUser|null  $zUser
     * @param  array  $data
     * @return ZCRMRecord
     * @throws ZCRMException
     */
    public static function createOrUpdateEvent(?ZCRMUser $zUser, array $data): ZCRMRecord
    {
        /** @var ZCRMModule $module22 */
        $module22 = ZCRMRestClient::getInstance()->getModule('CustomModule22')->getData();

        try {
            /** @var ZCRMRecord $eventRecord */
            $eventRecord = $module22->searchRecordsByCriteria("event_id:equals:{$data['event_id']}")->getData()[0];
        } catch (ZCRMException $exception) {
            /** @var ZCRMRecord $eventRecord */
            $eventRecord = ZCRMRecord::getInstance('CustomModule22', null);
        }

        $eventRecord->setFieldValue('event_id', "{$data['event_id']}");
        $eventRecord->setFieldValue('Name', $data['event_title']);
        $eventRecord->setFieldValue('field3', date('Y-m-d', $data['event_time_open']));
        $eventRecord->setFieldValue('field4', date('Y-m-d', $data['event_time_end']));
        $eventRecord->setFieldValue('field7', $data['event_category']);
        $eventRecord->setFieldValue('comments', $data['event_description']);
        $eventRecord->setFieldValue('field5', $data['event_first_ticket_price']);
        $eventRecord->setFieldValue('field8', $data['event_status']);
        $eventRecord->setFieldValue('event_location', $data['event_location']);
        if ($zUser) {
            $eventRecord->setFieldValue('Owner', intval($zUser->getId()));
        }

        if ($eventRecord->getEntityId()) {
            /** @var BulkAPIResponse $bulkResponse */
            $bulkResponse = $module22->updateRecords(array($eventRecord));
            /** @var EntityResponse $entityResponse */
            $entityResponse = $bulkResponse->getEntityResponses()[0];
            /** @var ZCRMRecord $eventRecord */
            $eventRecord = $entityResponse->getData();
        } else {
            /** @var BulkAPIResponse $bulkResponse */
            $bulkResponse = $module22->createRecords(array($eventRecord));
            /** @var EntityResponse $entityResponse */
            $entityResponse = $bulkResponse->getEntityResponses()[0];

            if ($entityResponse->getStatus() == self::ZCRM_STATUS_ERROR) {
                $exception = new ZCRMException($entityResponse->getMessage());
                $exception->setExceptionDetails($entityResponse->getResponseJSON());

                throw $exception;
            }

            /** @var ZCRMRecord $eventRecord */
            $eventRecord = $entityResponse->getData();
        }

        return $eventRecord;
    }

    /**
     * Find or create a new client record on Zoho.
     *
     * @param $eventRecord
     * @param $contactRecord
     * @param $data
     * @return ZCRMRecord
     *
     * @throws Exception
     * @todo replace Exception on custom custom ZCRMException
     */
    public static function findOrCreateClient($eventRecord, $contactRecord, $data): ZCRMRecord
    {
        /** @var ZCRMModule $module23 */
        $module23 = ZCRMRestClient::getInstance()->getModule('CustomModule23')->getData();

        try {
            return $module23->searchRecordsByCriteria("orderid:equals:{$data['order_id']}")->getData()[0];
        } catch (ZCRMException $exception) {
            // do nothing here and create a new client record next
        }

        $clientRecord = ZCRMRecord::getInstance('CustomModule23', null);

        $clientRecord->setFieldValue('orderid', $data['order_id']);
        $clientRecord->setFieldValue('event_id', $eventRecord->getEntityId());
        $clientRecord->setFieldValue('field2', $contactRecord->getEntityId());
        $clientRecord->setFieldValue('field6', $data['order_employment_status']);
        $clientRecord->setFieldValue('field4', [$data['order_education']]);

        $clientRecord = $module23->createRecords(array($clientRecord))->getData()[0];

        return $clientRecord;
    }

    /**
     * Find or create a new contact record on Zoho.
     *
     * @param $data
     * @return ZCRMRecord
     */
    public static function findOrCreateContact($data): ZCRMRecord
    {
        /** @var ZCRMModule $moduleContacts */
        $moduleContacts = ZCRMRestClient::getInstance()->getModule('Contacts')->getData();

        try {
            return $moduleContacts->searchRecordsByCriteria("(Mobile:equals:{$data['order_phone']})or(Email:equals:{$data['order_email']})")->getData()[0];
        } catch (ZCRMException $exception) {
            // do nothing and create a new contact next
        }
        $contactRecord = ZCRMRecord::getInstance('Contacts', null);

        $contactRecord->setFieldValue('Last_Name', "{$data['order_name_first']} {$data['order_name_last']}");
        $contactRecord->setFieldValue('order_id', $data['order_id']);
        $contactRecord->setFieldValue('Email_Opt_Out', boolval($data['order_mailing_ok']));
        $contactRecord->setFieldValue('Email', $data['order_email']);
        $contactRecord->setFieldValue('Mobile', $data['order_phone']);

        $contactRecord =  $moduleContacts->createRecords(array($contactRecord))->getData()[0];

        return $contactRecord;
    }
}