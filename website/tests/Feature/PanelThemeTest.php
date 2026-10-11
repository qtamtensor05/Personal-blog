<?php

use Filament\Facades\Filament;

it('keeps both admin panels light to match the custom editor surfaces', function () {
    foreach (['blog', 'platform'] as $id) {
        expect(Filament::getPanel($id)->hasDarkMode())->toBeFalse();
    }
});
