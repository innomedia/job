<?php

namespace Job;

use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use PageController;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Email\Email;
use HudhaifaS\Forms\FrontendFileField;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class JobsPageController extends PageController
{
    private static array $allowed_actions = [
        'BewerbungsForm',
        "PaginatedList",
        "job"
    ];

    public function doInit(): void
    {
        parent::doInit();
    }

    public function job() {
        $job = Job::get()->filter("URLSegment",$this->request->latestParam('ID'));
        if(count($job) === 1) {
            $templateData = [
                "Job" => $job->First(),
                'BackLink' => (($this->request->getHeader('Referer')) ? $this->request->getHeader('Referer') : $this->Link()),
            ];
            $this->extend('updateJobTemplateData', $job, $templateData);
            return $this->customise(new ArrayData($templateData))->renderWith(["Job","Page"]);
        }

        return false;
    }

    public function PaginatedList()
    {
        $allPosts = $this->owner->Jobs()->sort('Sort ASC') ?: new ArrayList();

        $posts = new PaginatedList($allPosts);

        // Set appropriate page size
        $pageSize = 10;
        $posts->setPageLength($pageSize);

        // Set current page
        $start = $this->owner->request->getVar($posts->getPaginationGetVar());
        $posts->setPageStart($start);

        return $posts;
    }

    public function BewerbungsForm()
    {
        if (array_key_exists("formsubmitted", $_REQUEST)) {
            return false;
        }
        
        $form = Form::create(
            $this,
            "BewerbungsForm",
            FieldList::create(
                TextField::create('Vorname','Vorname'),
                TextField::create('Nachname','Nachname'),
                EmailField::create('Email','Email'),
                HiddenField::create('Stelle','Stelle'
                ),
                TextareaField::create('Nachricht','Nachricht'
                ),
                $field = new FrontendFileField('Anhang', 'Anhang')
            ),
            FieldList::create(
                FormAction::create('handleForm', 'Abschicken')
                    ->setUseButtonTag(true)
                    ->addExtraClass('button orange_button')
            ),
            RequiredFieldsValidator::create('Vorname', 'Nachname', 'Email')
        );
        $field->setFolderName("bewerbungsfiles");

        $form->addExtraClass('form-style');

        return $form;
    }

    public function handleForm($data, $form)
    {
        //\SilverStripe\Dev\Debug::dump($data);die;
        $email = new Email();

        if ($this->dataRecord->BewerbungsMail != "") {
            $email->setTo($this->dataRecord->BewerbungsMail);
        } else {
            $email->setTo('bk@tietge.com');
        }
        
        $jobtitel = "";
        if ($data["Stelle"] == 0) {
            $jobtitel = "Initiativ Bewerbung";
            //InitiativBewerbung
        } else {
            $job = Job::get()->byID($data["Stelle"]);
            if ($job != null) {
                $jobtitel = $job->Title;
            }
        }

        $email->setFrom($data['Email']);
        $email->setSubject(sprintf('[%s] Bewerbung von %s %s', $jobtitel, $data["Vorname"], $data["Nachname"]));

        $messageBody = "
          <p><strong>Name:</strong> {$data['Vorname']} {$data["Nachname"]}</p>
      ";
        if ($data["Stelle"] > 0) {
            $messageBody .= "<p>
          Bewirbt sich hiermit für die Stelle {$jobtitel}
        </p>";
        }
        
        if ($data["Nachricht"] != "") {
            $messageBody .= "<h2>Nachricht</h2>
        <p>
          {$data["Nachricht"]}
        </p>";
        }
        ;
        $email->setBody($messageBody);
        if (array_key_exists("Anhang", $data)) {
            $email->addAttachment($data["Anhang"]["tmp_name"], $data["Anhang"]["name"], $data["Anhang"]["type"]);
        }

        $email->send();
        return "Danke für ihre Bewerbung";
    }
}
