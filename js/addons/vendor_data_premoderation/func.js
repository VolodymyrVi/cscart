(function (_, $) {
  $(_.doc).on('click', '.cm-submit.cm-update-company', function () {
    if ($('form.cm-vendor-changes-confirm').formIsChanged()) {
      if (confirm(_.tr('text_vendor_profile_changes_notice')) == false) {
        return false;
      }
    }
  });
  $(document).ready(function () {
    if (_.vendor_pre == 'Y') {
      $('form#company_update_form').addClass('cm-vendor-changes-confirm');
    }
  });
  $(_.doc).on('click', '[data-ca-premoderation-disapprove]', function (e) {
    e.preventDefault();
    var $productStatusContainer = $(this).closest('[data-ca-product-status-container]'),
        $reasonSection = $productStatusContainer.find('[data-ca-product-disapproval-reason-section]'),
        $reasonInput = $productStatusContainer.find('[data-ca-product-disapproval-reason]'),
        $reasonText = $productStatusContainer.find('[data-ca-product-disapproval-reason-text]'),
        $disapproveBtn = $productStatusContainer.find('[data-ca-premoderation-disapprove]'),
        $statusInputs = $productStatusContainer.find(':input');

    if ($reasonSection.data('caProductDisapprovalReasonSection')) {
      $statusInputs.prop('disabled', true);
      $disapproveBtn.removeClass('btn-primary');
      $reasonText.removeClass('hidden');

      if ($reasonText.text() === '') {
        $reasonSection.addClass('hidden');
      }

      $reasonInput.val('').addClass('hidden');
      $reasonSection.data('caProductDisapprovalReasonSection', false);
    } else {
      $statusInputs.prop('disabled', false);
      $disapproveBtn.addClass('btn-primary');
      $reasonText.addClass('hidden');

      if ($reasonText.text() === '') {
        $reasonSection.removeClass('hidden');
      }

      $reasonInput.removeClass('hidden').focus();
      $reasonSection.data('caProductDisapprovalReasonSection', true);
    }
  });
})(Tygh, Tygh.$);