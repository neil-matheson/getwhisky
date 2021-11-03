<?php 
require_once("../page.class.php");
require_once("../util.class.php");

if (isset($_POST['function']) && util::valInt($_POST['function'])) {
    $functionToCall = $_POST['function'];
    switch ($functionToCall) {
        case 1:
            getFilteredProducts();
        break;
        case 2:
            addToCart();
            break;
    }
}

function getFilteredProducts() {
    $page = new Page();
    // Retrieve all products from page class
    $allProducts = $page->getProducts();
    $filteredProducts = [];
    $htmlToReturn = "";
    
    // Category passed filter products by category
    if (isset($_POST['catid']) && util::valInt($_POST['catid'])) {
        $categoryId = util::sanInt($_POST['catid']);
        foreach($allProducts as $product) {
            if ($product->getCategoryId() == $categoryId) {
                array_push($filteredProducts, $product);
                $allProducts = $filteredProducts;
            }
        }


        // Additional filters passed, further filter the products
        if (isset($_POST['attribute_values'])) {
            // Sanitize all array inputs
            $attributeValues = array_filter($_POST['attribute_values'], "ctype_digit");
            // reset filtered products to empty array
            $filteredProducts = [];
            
            // Iterate through all products
            foreach ($allProducts as $product) {
                // if multiple filters passed
                if (count($attributeValues) > 1) {
                    // Ensure total filter equality
                    $arraysAreEqual = ($product->getAttributes() == $attributeValues);
                    if ($arraysAreEqual) {
                        array_push($filteredProducts, $product);
                    }
                } else {
                    // Only one filter passed, loop through and check each index
                    foreach($attributeValues as $attributeValue) {
                        if (in_array($attributeValue, $product->getAttributes())) {
                            array_push($filteredProducts, $product);
                        }
                    }
                }
            }
        }

        
        foreach($filteredProducts as $product) {
            $htmlToReturn.=$product;
        }

        echo $htmlToReturn;
    }
    
}


?>