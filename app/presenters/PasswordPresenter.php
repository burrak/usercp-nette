<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class PasswordPresenter extends BasePresenter
{
    public function startup(): void
    {
        parent::startup();
        if (($this->getUser()->isLoggedIn()) && ($this->getUser()->getIdentity() !== null)) {
            return;
        }
        $this->flashMessage('Do této části aplikace nemáte přístup bez přihlášení. Prosím přihlašte se.', 'alert alert-danger');
        $this->redirect('Homepage:');
    }

    public function createComponentPasswordForm(): Form
    {
        $form = new Form;
        $form->addPassword('oldPass', 'Staré heslo:')
            ->setRequired('Musíš zadat steré heslo');
        $form->addPassword('newPass', 'Nové heslo:')
            ->setRequired('Musíš zadat nové heslo');
        $form->addPassword('passCheck', 'Heslo pro kontrolu:')
            ->addRule(Form::EQUAL, 'Hesla nesouhlasí', $form['newPass'])
            ->setRequired('Musíš zadat heslo pro kontrolu');
        $form->addSubmit('send', 'Změnit heslo');
        $form->addProtection('Platnost formuláře vypršela.');
        $form->onSuccess[] = [$this, 'changePasswordSucceeded'];
        return $form;
    }

    public function changePasswordSucceeded(Form $form): void
    {
        $values = $form->getValues();
        if ($this->user->checkPassword($this->getUser()->getId(), $values['oldPass']) !== true)
        {
            $this->flashMessage('Špatné heslo', 'alert alert-danger');
            $this->redirect('Password:');
        }

        if ($values['oldPass'] === $values['newPass'])
        {
            $this->flashMessage('Nové heslo nemůže být stejné jako staré', 'alert alert-warning');
            $this->redirect('Password:');
        }
        $this->user->changePassword($this->getUser()->getId(), $values['newPass']);
        $this->flashMessage('Heslo bylo změněno', 'alert alert-success');
        $this->redirect('Password:');
    }

}