<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class TradePresenter extends BasePresenter
{
    /** @var \App\Model\Characters
     *  @inject
     */
    public $char;

    public function startup(): void
    {
        parent::startup();
        if (($this->getUser()->isLoggedIn()) && ($this->getUser()->getIdentity() !== null)) {
            return;
        }
        $this->flashMessage('Do této části aplikace nemáte přístup bez přihlášení. Prosím přihlašte se.', 'alert alert-danger');
        $this->redirect('Homepage:');
    }

    public function createComponentTradeForm(): Form
    {
        $chars = $this->char->getPostavy($this->getUser()->getId(), true, true);
        $postavyTradeList = array('');
        foreach ($chars as $key => $value) {
            $postavyTradeList[$value['guid']] = $value['name'] . ' (' . $value['race'] . ' ' . $value['class'] . ' level ' . $value['level'] . ')';
        }

        $form = new Form;
        $form->addSelect('char_owned', 'Vyměnit postavu', $postavyTradeList);
        $form->addText('char_wanted', 'za: ')
            ->setRequired('Musíš zadat postavu, kterou požaduješ');
        $form->addSubmit('send', 'zažádat o výměnu');
        $form->addProtection('Vypršela platnost formuláře');
        return $form;
    }

    public function handleConfirm(int $tradeId): void
    {
        $this->char->confirmTrade($tradeId);
        $this->flashMessage('Postavy byly vyměněny', 'alert alert-success');
        $this->redirect('this');
    }

    public function handleCancel(int $tradeId): void
    {
        $this->char->cancelTrade($tradeId);
        $this->flashMessage('Výměna postav byla odmítnuta', 'alert alert-warning');
        $this->redirect('this');

    }

    public function renderDefault(): void
    {
        $this->template->tradeByMe = $this->char->tradeByMe($this->getUser()->getId());
        $this->template->tradeToMe = $this->char->tradeToMe($this->getUser()->getId());
    }

}