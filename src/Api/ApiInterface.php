<?php

namespace Ammonkc\Ptpkg\Api;

/**
 * Api interface.
 *
 * @author Ammon Casey <ammon@caseyohana.com>
 */
interface ApiInterface
{
    public function getPerPage();

    public function setPerPage($perPage);
}
