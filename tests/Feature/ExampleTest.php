<?php

test('la page d’accueil répond', function () {
    $this->get('/')->assertStatus(200);
});
