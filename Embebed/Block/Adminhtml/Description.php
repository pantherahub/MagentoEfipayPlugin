<?php

namespace EfipayPayment\Embebed\Block\Adminhtml;

use Magento\Backend\Block\AbstractBlock;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface as RendererInterface;

class Description extends AbstractBlock implements RendererInterface
{
    public function __construct(DirectoryList $dir)
    {
        $this->_dir = $dir;
    }
    
    public function render(AbstractElement $element)
    {
        $html = '<div class="section-config with-button""> 
                    <div class="config-heading">
                        <div class="row-heading">
							<div class="logos">
								<img src="https://efipay.co/images/brands/visa.svg" alt="visa.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/master card.svg" alt="master card.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/american express.svg" alt="american express.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/diners-club.svg" alt="diners-club.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/nequi.svg" alt="nequi.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/pse.svg" alt="pse.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/daviplata.svg" alt="daviplata.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/pinos.svg" alt="pinos.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/exito.svg" alt="exito.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/surti mayorista.svg" alt="surti mayorista.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/servypagos.svg" alt="servypagos.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/edeq.svg" alt="edeq.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/carulla.svg" alt="carulla.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/super inter.svg" alt="super inter.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/moviired.svg" alt="moviired.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/copetran.svg" alt="copetran.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/surtimax.svg" alt="surtimax.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/mercar.svg" alt="mercar.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/efecty.svg" alt="efecty.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/laperla.svg" alt="laperla.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/apuestas cucuta.svg" alt="apuestas cucuta.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/super giros.svg" alt="super giros.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/red.svg" alt="red.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/suchance.svg" alt="suchance.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/gana gana.svg" alt="gana gana.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/acertemos.svg" alt="acertemos.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/paga todo para todo.svg" alt="paga todo para todo.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/gana.svg" alt="gana.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/jer.svg" alt="jer.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/ptm.svg" alt="ptm.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/reval.svg" alt="reval.svg" width="70" height="30" class="object-fit-contain">
								<img src="https://efipay.co/images/brands/su red.svg" alt="su red.svg" width="70" height="30" class="object-fit-contain">
							</div>
							<div>
								<small>'.MPEFIPAY_PLUGIN_VERSION.'</small>
								<strong class="text-right">
									<a class="link-more" href="https://sag.efipay.co/docs/1.0/magento-integration" target="_blank"> Documentación Efipay </a>
								</strong>
							</div>
                        </div>
                    </div>
                 </div>';
        return $html;
    }
}