<?php

defined('ABSPATH') || exit;

?>
<template data-rbf-product-family-template>
	<article class="rbf-product-family-item" data-rbf-product-family-item>
		<a class="rbf-product-family-item__link" href="" data-rbf-family-link>

			<div class="rbf-product-family-item__image rbf-img-wrap">
				<figure class="rot-img-relative rbf-product-family-item__img" hidden data-rbf-product-family-image>
					<img src="" alt="" loading="lazy" decoding="async" data-rbf-product-family-image-src>
				</figure>

				<div class="rbf-product-family-item__image-placeholder" data-rbf-product-family-image-placeholder>
					Product Family
				</div>
			</div>

			<div class="rbf-product-family-item__content">

				<h3 class="rbf-product-family-item__title" data-rbf-family-title></h3>

				<div class="rbf-product-family-item__strengths" data-rbf-family-strengths></div>

				<p class="rbf-product-family-item__strengths-label">
					erhältlich in den Gliederstärken:
					<span data-rbf-family-strengths-text></span>
				</p>

			</div>

		</a>
	</article>
</template>