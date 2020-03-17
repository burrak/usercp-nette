<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class HomepagePresenter extends BasePresenter
{

    public function createComponentLoginForm(): Form
    {
        $form = New Form;
        $form->addText('login', 'Login:')
            ->setRequired('Je nutné vyplnit login');
        $form->addPassword('password', 'Heslo:')
            ->setRequired('Je nutné vyplnit heslo');
        $form->addSubmit('send', 'Login');
        $form->addProtection('Platnost formuláře vypršela, zkuse to znovu');
        $form->onSuccess[] = [$this, 'loginFormSucceeded'];
        return $form;
    }

    public function loginFormSucceeded(Form $form): void
    {
        try {
            $this->getUser()->login($form->getValues()->login, $form->getValues()->password);
            $this->flashMessage('Přihlášení bylo úspěšné.', 'alert alert-success');
            $this->redirect('Characters:');
        } catch (\Nette\Security\AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'alert alert-danger');
            $this->redirect('Homepage:');
        }
    }

    public function createComponentRegForm(): Form
    {
        $form = New Form();
        $form->addText('login', 'Login:')
            ->setRequired('Je nutné vyplnit login');
        $form->addPassword('password', 'Heslo:')
            ->setRequired('Je nutné vyplnit heslo');
        $form->addPassword('password_confirm', 'Kontrola hesla')
            ->setRequired('Je nutné vyplnit heslo pro kontrolu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);
        $form->addEmail('email', 'E-mail:')
            ->setRequired('Je nutné vyplnit e-mail')
            ->addRule(Form::EMAIL, 'E-mail je ve špatném formátu');
        $form->addSubmit('send', 'Registrovat');
        $form->addProtection('Platnost fromuláře vypršela.');
        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $form;
    }

    /**
     * @param \Nette\Application\UI\Form $form
     * @throws \Nette\Application\AbortException
     * @var mixed[] $values
     */
    public function registerFormSucceeded(Form $form): void
    {
        $values = $form->getValues();
        try {
            $this->user->registrace($values['login'], $values['password'], $values['email']);
            $this->flashMessage('Registrace proběhla úspěšně', 'alert alert-success');
            $this->redirect('Homepage:');
        } catch (\Nette\Security\AuthenticationException $e) {
            $this->flashMessage($e->getMessage(), 'alert alert-danger');
            $this->redirect('Homepage:register');
        }

    }

    public function actionLogout(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.', 'alert alert-success');
        $this->redirect('Homepage:');
    }

    public function actionRegister(): void
    {

    }

    public function renderDefault(): void
    {

    }
}
