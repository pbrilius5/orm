<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">Edit Group</h5>
                </div>
                <div class="card-body p-4">
                    <?php
                    $formHelperClass = $formHelper ?? \App\View\Helper\FormHelper::class;
                    echo $formHelperClass::renderForm($form);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
