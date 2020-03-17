<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class CharactersPresenter extends BasePresenter
{
    /** @var \App\Model\Characters
     * @inject
     */
    public $chars;

    public function renderDefault(): void
    {
        $this->template->characters = $this->chars->getPostavy($this->getUser()->getId(), true, true);

    }

    public function actionDetail(int $guid): void
    {
        $this->template->character = $this->chars->returnPostava($guid, true, true);
        $this->template->services = $this->chars->returnSluzby();
    }

    public function handleService(int $service, int $guid): void
    {
        $character = $this->chars->returnPostava($guid, false, false);
        $charCheck = $this->chars->postavaCheck($this->getUser()->getId(), $guid);
        $credits = $this->chars->priceCheck($this->getUser()->getId(), $service);
        if ($credits === null) {
            $this->flashMessage('Nemáš dostatek kreditů', 'alert alert-warning');
            $this->redirect('Characters:detail', $guid);
        }
        if ($charCheck === 'char_not_exist') {
            $this->flashMessage('Postava neexistuje', 'alert alert-danger');
            $this->redirect('Characters:');
        }
        if ($charCheck === 'char_not_owned') {
            $this->flashMessage('Tato postava ti nepatří', 'alert alert-danger');
            $this->redirect('Characters:');
        }

        if ($character['online'] === 1) {
            $this->flashMessage('Postava musí být offline', 'alert alert-danger');
            $this->redirect('Characters:detail', $guid);
        }

        switch ($service) {
            case 1:
                $query = $this->chars->raceChange($this->getUser()->getId(), $guid, $credits);
                if ($query === false) {
                    $this->flashMessage('Postava ' . $character['name'] . ' již má změnu rasy', 'alert alert-warning');
                    $this->redirect('Characters:detail', $guid);
                }
                $this->flashMessage('Byla vyvolána změna rasy u postavy ' . $character['name'], 'alert alert-success');
                $this->redirect('Characters:detail', $guid);
                break;
            case 2:
                $query = $this->chars->rename($this->getUser()->getId(), $guid, $credits);
                if ($query === false) {
                    $this->flashMessage('Postava ' . $character['name'] . ' již má rename', 'alert alert-warning');
                    $this->redirect('Characters:detail', $guid);
                }
                $this->flashMessage('Byl vyvolán rename u postavy ' . $character['name'], 'alert alert-success');
                $this->redirect('Characters:detail', $guid);
                break;

            case 3:
                $query = $this->chars->factionchange($this->getUser()->getId(), $guid, $credits);
                if ($query === false) {
                    $this->flashMessage('Postava ' . $character['name'] . ' již má změnu frakce', 'alert alert-warning');
                    $this->redirect('Characters:detail', $guid);
                }
                $this->flashMessage('Byla vyvolána změna frakce u postavy ' . $character['name'], 'alert alert-success');
                $this->redirect('Characters:detail', $guid);
                break;

            case 4:
                $query = $this->chars->apperancechange($this->getUser()->getId(), $guid, $credits);
                if ($query === false) {
                    $this->flashMessage('Postava ' . $character['name'] . ' již má změnu vzhledu', 'alert alert-warning');
                    $this->redirect('Characters:detail', $guid);
                }
                $this->flashMessage('Byla vyvolána změna vzhled upostavy ' . $character['name'], 'alert alert-success');
                $this->redirect('Characters:detail', $guid);
                break;

            case 5:
                $this->chars->unstuck($this->getUser()->getId(), $guid, $credits);
                $this->flashMessage('Byl Proveden unstuck u postavy ' . $character['name'], 'alert alert-success');
                $this->redirect('Characters:detail', $guid);
                break;
        }
        $this->flashMessage('Vyskytla se chyba', 'alert alert-warning');
        $this->redirect('Characters:detail', $guid);

    }

    public function actionRestore(): void
    {


    }

    public function createComponentRestoreChars(): Form
    {
        $smazane = $this->chars->returnSmazanePostavy($this->getUser()->getId(), true, true);
        if (\is_array($smazane) && \count($smazane) > 0) {
            $smazaneList = array('');
            foreach ($smazane as $key => $value) {
                $smazaneList[$value['guid']] = $value['deleteInfos_Name'] . ' (' . $value['race'] . ' ' . $value['class'] . ' level ' . $value['level'] . ')';
            }

            $form = new Form;
            $form->addSelect('character', 'Postava:', $smazaneList);
            $form->addSubmit('send', 'Obnovit postavu');
            $form->addProtection('Platnost formuláře vypršela');
            $form->onSuccess[] = [$this, 'restoreCharacterSucceeded'];
            return $form;
        }
            $form = new Form;
            $form->addText('nothing', '')->setDisabled()->setValue('Žádné postavy k obnovení');
            return $form;
    }

    public function restoreCharacterSucceeded(Form $form): void
    {
        $values = $form->getValues();
        $character = $this->chars->returnPostava($values['character'], false, false);
        $charCheck = $this->chars->postavaCheck($this->getUser()->getId(), $values['character'], 1);
        if ($charCheck === 'char_not_exist') {
            $this->flashMessage('Postava neexistuje', 'alert alert-danger');
            $this->redirect('Characters:restore');
        }
        if ($charCheck === 'char_not_owned') {
            $this->flashMessage('Tato postava ti nepatří', 'alert alert-danger');
            $this->redirect('Characters:restore');
        }
        $this->chars->obnovPostavu($values['character']);
        $this->flashMessage('Postava ' . $character['name'] . ' byla obnovena', 'alert alert-success');
        $this->redirect('Characters:');
    }
}