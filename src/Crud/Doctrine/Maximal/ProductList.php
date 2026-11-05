<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Component\Crud\CrudList;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The list of the products, drawn by a template of its own that extends the bundle's and changes one block (what it
 * says when nothing is found). A live action of its own would go here too (#[LiveAction], LiveListAction in
 * ProductCrud); a change of a record is better a RunAction (ToggleProductActive): guarded and heard like the form.
 */
#[AsLiveComponent('App:ProductList', template: 'components/App/ProductList.html.twig')]
final class ProductList extends CrudList
{
}
