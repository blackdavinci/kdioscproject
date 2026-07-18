<?php

test('la racine redirige vers le panel de l’organisation', function () {
    $this->get('/')->assertRedirect('/app');
});
