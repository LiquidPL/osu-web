<?php

/**
 *    Copyright 2015-2017 ppy Pty. Ltd.
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

namespace App\Libraries\Elasticsearch;

trait HasSearch
{
    protected $from;
    protected $highlight;
    protected $query;
    protected $size;
    protected $sorts = [];
    protected $source;
    protected $type;

    /**
     * @return $this
     */
    public function from(?int $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return $this
     */
    public function limit(?int $limit)
    {
        return $this->size($limit);
    }

    /**
     * @return $this
     */
    public function size(?int $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return $this
     */
    public function page(?int $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param Highlight $highlight the fields and settings for highlighting. Set to null to remove.
     *
     * @return $this
     */
    public function highlight(?Highlight $highlight)
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * The query for the search.
     * array is supported for compatiblity and more complicated/unimplemented stuff.
     *
     * @param array|Queryable
     *
     * @return $this
     */
    public function query($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return $this
     */
    public function source($fields)
    {
        $this->source = $fields;

        return $this;
    }

    /**
     * @param array|Sort $sort
     *
     * @return $this
     */
    public function sort($sort)
    {
        if (is_array($sort)) {
            foreach ($sort as $s) {
                $this->addSort($s);
            }
        } else {
            $this->addSort($sort);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function type(?string $type)
    {
        $this->type = $type;

        return $this;
    }

    protected function getDefaultSize() : int
    {
        return 10;
    }

    /**
     *  Gets the actual offset to use in queries.
     *
     * @return int actual offset to use.
     */
    protected function getFrom() : int
    {
        return $this->from ?? $this->getSize() * ($this->getPage() - 1);
    }

    /**
     *  Gets the actual size to use in queries.
     *
     * @return int actual size to use.
     */
    protected function getQuerySize() : int
    {
        return min($this->maxResults() - $this->getFrom(), $this->getSize());
    }

    /**
     *  Gets the size or default size if none was give..
     *
     * @return int size.
     */
    protected function getSize() : int
    {
        return $this->size ?? $this->getDefaultSize();
    }

    protected function maxResults() : int
    {
        // the default is the maximum number of total results allowed when not using the scroll API.
        return 10000;
    }

    private function addSort(Sort $sort)
    {
        if (!$sort->isBlank()) {
            $this->sorts[] = $sort;
        }
    }

    private function getPage() : int
    {
        return max(1, $this->page ?? 1);
    }
}
