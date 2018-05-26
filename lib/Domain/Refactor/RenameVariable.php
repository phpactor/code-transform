<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface RenameVariable
{

    public function renameVariable(SourceCode $source, int $offset, string $newName, string $scope = RenameVariable::SCOPE_FILE): SourceCode;
}
