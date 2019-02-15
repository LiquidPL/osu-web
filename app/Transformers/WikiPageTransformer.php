<?php

/**
 *    Copyright (c) ppy Pty Ltd <contact@ppy.sh>.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Wiki\Page;

class WikiPageTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['content'];

    public function transform(Page $page) {
        return [
            'title' => $page->title(),
            'path' => $page->path,
            'source_path' => $page->pagePath(),
            'edit_url' => $page->editUrl(),
            'outdated' => $page->isOutdated(),
            'legal_translation' => $page->isLegalTranslation(),
            'tags' => $page->tags(),
        ];
    }

    public function includeContent(Page $page) {
        return $this->item($page, function ($page) {
            return [$page->pageContent()];
        });
    }
}
