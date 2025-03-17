<?php  
namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\ProductCatalogue;
// use App\Services\Interfaces\WidgetServiceInterface  as WidgetService;
use App\Repositories\Interfaces\ProductCatalogueRepositoryInterface as ProductCatalogueRepository;


class ProductCatalogueComposer
{

    protected $widgetService;
    protected $productCatalogueRepository;

    public function __construct(
        // WidgetService $widgetService,
        ProductCatalogueRepository $productCatalogueRepository,
    ){
    //    $this->widgetService = $widgetService;
       $this->productCatalogueRepository = $productCatalogueRepository;
    }

    public function compose(View $view)
    {
        
        $categories = $this->productCatalogueRepository->all();
        
        // $categories = $this->productCatalogueRepository->getProductCatalogueByPublish(2);

        
        $view->with('categories', $categories);
    }

   

}