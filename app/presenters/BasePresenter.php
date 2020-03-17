<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Security\Identity;
use Nette\Security\User;
use Nette\Application\UI\Presenter;
use Nette\Security\IIdentity;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var \App\Model\User
     *  @inject
     */
    public $user;

    public function beforeRender(): void
    {
        parent::beforeRender();
        if ($this->template instanceof Nette\Bridges\ApplicationLatte\Template) {
            $this->template->addFilter('formatGold', function (int $number) {
                $number = (string)$number;
                $v = \substr_replace($number, ' g ', -4, 0);
                return \substr_replace($v, ' s ', -2, 0) . ' c';
            });
            $this->template->addFilter('formatSilver', function (int $number) {
                $number = (string)$number;
                return \substr_replace($number, ' s ', -2, 0) . ' c';
            });
            $this->template->addFilter('formatCooper', function (int $number) {
                $number = (string)$number;
                return $number . ' c';
            });
        }


        if($this->getUser()->getIdentity() === null) {
            return;
        }
        $this->template->credits = $this->user->returnCredits($this->getUser()->getIdentity()->getId());
        $this->template->lastIp = $this->user->returnLastIp($this->getUser()->getIdentity()->getId());
    }
}