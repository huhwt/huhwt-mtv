<?php

/**
 * HuH Extensions for webtrees - Multi-Treeview
 * Extensions for webtrees to check and display duplicate Individuals in the database.
 * Copyright (C) 2020-2023 EW.Heinrich
 * 
 * Coding for the configuration in Admin-Panel goes here
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods\Traits;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait MultTVconfigTrait {

    /**
     * Filter on n_type
     *
     * @return array<int,string>
     */
    public function ntypeConfigOptions(): array
    {
        return [
            0   => I18N::translate('(omitted)'),
            // EW.H - MOD ...     Achtung, Text wird ab '>' so wie geschrieben übernommen!
            1   => I18N::translate('only') . " ->'NAME'"
        ];
    }

    /**
     * Filter on d_fact
     * 
     * @return array<int,string>
     */
    private function dfactConfigOptions(): array
    {
        return [
            0   => I18N::translate('(omitted)'),
            // EW.H - MOD ...     Achtung, Text wird ab '>' so wie geschrieben übernommen!
            1   => I18N::translate('Match only in') . " ->'BIRT', 'CHR', 'BAPM', 'DEAT', 'BURI'"
        ];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->name() . '::settings', [
            'ntypeOption'       => (int) $this->getPreference('ntype_Option', '0'),
            'ntype_options'     => $this->ntypeConfigOptions(),
            'dfactOption'       => (int) $this->getPreference('dfact_Option', '0'),
            'dfact_options'     => $this->dfactConfigOptions(),
            'title'             => I18N::translate('Chart preferences') . ' — ' . $this->title(),
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $ntypeOption = Validator::parsedBody($request)->integer('ntypeOption');
        $dfactOption = Validator::parsedBody($request)->integer('dfactOption');

        $this->setPreference('ntype_Option', (string) $ntypeOption);
        $this->setPreference('dfact_Option', (string) $dfactOption);

        FlashMessages::addMessage(I18N::translate('The preferences for the module “%s” have been updated.', $this->title()), 'success');

        return redirect($this->getConfigLink());
    }


}