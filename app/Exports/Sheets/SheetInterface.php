<?php

namespace App\Exports\Sheets;

/**
 * Contratto generico di un foglio dell'export: sa validarsi e sa scriversi.
 *
 *  - validate(): valida la config che arriva dal client (rilevante per i fogli
 *    dati richiedibili; per i metadata non c'è nulla da validare).
 *  - title()/rows(): identità e contenuto del foglio per la scrittura nell'xlsx.
 */
interface SheetInterface
{
    /**
     * @return string[] messaggi d'errore (vuoto se la config è valida)
     */
    public function validate(array $config): array;

    /**
     * Nome del foglio (tab) nel file.
     */
    public function title(): string;

    /**
     * Righe del foglio: ogni elemento è un array di celle scalari.
     *
     * @return iterable
     */
    public function rows(): iterable;
}
