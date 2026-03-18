<?php
require 'db.php';

foreach(['estudiantes','pnfs','residencias','trabajos','records_academicos','materias','estudiante_materias','familiares'] as $t){
    $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "$t: $c\n";
}
