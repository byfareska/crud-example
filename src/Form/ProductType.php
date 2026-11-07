<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use Byfareska\Crud\Files\Form\CrudFileType;
use Byfareska\Crud\Form\CrudRichTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The fields of the product's add and edit forms. The driver gives the form the product being edited (a new one when
 * adding), so a field can depend on it: see "active". The edit form is given "sku_fixed" (ProductCrud: updateForm), so
 * the SKU is not changed once given.
 */
final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['data'] ?? null;
        // A saved product out of stock is not activated: the box is not to be ticked (and what is sent for a disabled
        // field is ignored). Only a convenience - the rule itself is Product::validateActive(): the box goes by the
        // stock saved, not by the one being typed, and a product left active without stock can still be deactivated.
        $noStock = $product instanceof Product && $product->id !== null && $product->stock === 0 && !$product->active;

        $builder
            ->add('name', TextType::class, ['label' => 'crud.product.field.name.name', 'empty_data' => ''])
            ->add('sku', TextType::class, [
                'label' => 'crud.product.field.sku.name',
                'help' => 'crud.product.field.sku.help',
                'empty_data' => '',
                'disabled' => $options['sku_fixed'],
            ])
            ->add('category', EntityType::class, [
                'label' => 'crud.product.field.category.name',
                'class' => Category::class,
                'required' => false,
                'placeholder' => 'crud.product.field.category.none',
            ])
            // Rich text: images pasted, dropped or picked from the library go to the public storage, put in as "medium";
            // a chosen image has the menu of its sizes.
            ->add('description', CrudRichTextType::class, [
                'label' => 'crud.product.field.description.name',
                'files' => ['accept' => ['images'], 'sizes' => ['large', 'medium'], 'size' => 'medium'],
            ])
            ->add('price', MoneyType::class, ['label' => 'crud.product.field.price.name', 'currency' => 'PLN', 'input' => 'string'])
            ->add('stock', IntegerType::class, ['label' => 'crud.product.field.stock.name'])
            ->add('active', CheckboxType::class, [
                'label' => 'crud.product.field.active.name',
                'required' => false,
                'disabled' => $noStock,
                'help' => $noStock ? 'crud.product.field.active.no_stock' : null,
            ])
            // A gallery: images only, in the order they are dragged to, each with its alternative text - kept as
            // ProductImage entities (their file, position and alt).
            ->add('images', CrudFileType::class, [
                'label' => 'crud.product.field.images.name',
                'multiple' => true,
                'accept' => ['images'],
                'meta' => ['alt'],
                'binding' => 'items',
                'item_class' => ProductImage::class,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class, 'sku_fixed' => false]);
        $resolver->setAllowedTypes('sku_fixed', 'bool');
    }
}
