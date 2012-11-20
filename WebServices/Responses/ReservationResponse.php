<?php
/**
Copyright 2012 Nick Korbel

This file is part of phpScheduleIt.

phpScheduleIt is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

phpScheduleIt is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with phpScheduleIt.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'lib/WebService/namespace.php');
require_once(ROOT_DIR . 'WebServices/Responses/RecurrenceResponse.php');
require_once(ROOT_DIR . 'WebServices/Responses/ResourceItemResponse.php');
require_once(ROOT_DIR . 'WebServices/Responses/AccessoryItemResponse.php');
require_once(ROOT_DIR . 'WebServices/Responses/CustomAttributeResponse.php');
require_once(ROOT_DIR . 'WebServices/Responses/AttachmentResponse.php');
require_once(ROOT_DIR . 'WebServices/Responses/ReservationUserResponse.php');

class ReservationResponse extends RestResponse
{
	public $referenceNumber;
	public $startDate;
	public $endDate;
	public $title;
	public $description;
	public $requiresApproval;
	public $isRecurring;
	public $scheduleId;
	public $resourceId;
	public $owner;
	public $participants = array();
	public $invitees = array();
	public $customAttributes = array();
	public $recurrenceRule;
	public $attachments = array();
	/**
	 * @var array|ResourceItemResponse[]
	 */
	public $resources = array();
	public $accessories = array();

	/**
	 * @param IRestServer $server
	 * @param ReservationView $reservation
	 * @param IPrivacyFilter $privacyFilter
	 * @param array|CustomAttribute[] $attributes
	 */
	public function __construct(IRestServer $server,
								ReservationView $reservation,
								IPrivacyFilter $privacyFilter,
								$attributes = array())
	{
		$this->owner = ReservationUserResponse::Masked();

		$canViewUser = $privacyFilter->CanViewUser($server->GetSession(), $reservation);
		$canViewDetails = $privacyFilter->CanViewDetails($server->GetSession(), $reservation);

		$this->referenceNumber = $reservation->ReferenceNumber;
		$this->startDate = $reservation->StartDate->ToIso();
		$this->endDate = $reservation->EndDate->ToIso();
		$this->requiresApproval = $reservation->RequiresApproval();
		$this->isRecurring = $reservation->IsRecurring();
		$this->recurrenceRule = new RecurrenceResponse($reservation->RepeatType, $reservation->RepeatInterval, $reservation->RepeatMonthlyType, $reservation->RepeatWeekdays);
		$this->resourceId = $reservation->ResourceId;
		$this->scheduleId = $reservation->ScheduleId;
		$this->AddService($server, WebServices::GetSchedule,
						  array(WebServiceParams::ScheduleId => $reservation->ScheduleId));

		foreach ($reservation->Resources as $resource)
		{
			$this->resources[] = new ResourceItemResponse($server, $resource->Id(), $resource->GetName());
		}

		foreach ($reservation->Accessories as $accessory)
		{
			$this->accessories[] = new AccessoryItemResponse($server, $accessory->AccessoryId, $accessory->Name, $accessory->QuantityReserved);
		}

		if ($canViewDetails)
		{
			$this->title = $reservation->Title;
			$this->description = $reservation->Description;
			foreach ($attributes as $attribute)
			{
				$this->customAttributes[] = new CustomAttributeResponse($server, $attribute->Id(),
																		$attribute->Label(),
																		$reservation->GetAttributeValue($attribute->Id()));
			}
			foreach ($reservation->Attachments as $attachment)
			{
				$this->attachments[] = new AttachmentResponse($server, $attachment->FileId(), $attachment->FileName(), $reservation->ReferenceNumber);
			}
		}

		if ($canViewUser)
		{
			$this->owner = new ReservationUserResponse($server, $reservation->OwnerId, $reservation->OwnerFirstName,
													   $reservation->OwnerLastName,
													   $reservation->OwnerEmailAddress);
			foreach ($reservation->Participants as $participant)
			{
				$this->participants[] = new ReservationUserResponse($server, $participant->UserId,
																	$participant->FirstName,
																	$participant->LastName, $participant->Email);
			}
			foreach ($reservation->Invitees as $invitee)
			{
				$this->invitees[] = new ReservationUserResponse($server, $invitee->UserId,
																$invitee->FirstName, $invitee->LastName,
																$invitee->Email);
			}
		}
	}


	/**
	 * @return ReservationResponse
	 */
	public static function Example()
	{
		return new ExampleReservationResponse();
	}
}

class ExampleReservationResponse extends ReservationResponse
{
	public function __construct()
	{
		$this->accessories = array(AccessoryItemResponse::Example());
		$this->attachments = array(AttachmentResponse::Example());
		$this->customAttributes = array(CustomAttributeResponse::Example());
		$this->description = 'reservation description';
		$this->endDate = Date::Now()->ToIso();
		$this->invitees = array(ReservationUserResponse::Example());
		$this->isRecurring = true;
		$this->owner = ReservationUserResponse::Example();
		$this->participants = array(ReservationUserResponse::Example());
		$this->recurrenceRule = RecurrenceResponse::Example();
		$this->referenceNumber = 'refnum';
		$this->requiresApproval = true;
		$this->resourceId = 123;
		$this->resources = array(ResourceItemResponse::Example());
		$this->scheduleId = 123;
		$this->startDate = Date::Now()->ToIso();
		$this->title = 'reservation title';
	}
}

?>