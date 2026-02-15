<div class="col-12 mb-5">
    <div class="position-relative overflow-hidden rounded-4 shadow-lg text-white" 
         style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); min-height: 320px;">
        
        <div class="position-absolute top-0 end-0 bg-white opacity-10 rounded-circle blur-3xl" style="width: 400px; height: 400px; margin-top: -150px; margin-right: -100px;"></div>
        <div class="position-absolute bottom-0 start-0 bg-white opacity-10 rounded-circle blur-3xl" style="width: 300px; height: 300px; margin-bottom: -100px; margin-left: -50px;"></div>
        
        <div class="position-relative z-1 d-flex flex-column justify-content-center align-items-center h-100 py-5 px-4 text-center">
            <span class="badge bg-white bg-opacity-20 backdrop-blur border border-white border-opacity-25 rounded-pill px-3 py-2 mb-3 fw-normal">
                👋 Добро пожаловать в Туркестан
            </span>
            
            <h1 class="display-4 fw-bold mb-3 ls-tight">Найдите лучшие места города</h1>
            <p class="fs-5 text-white-50 mb-4" style="max-width: 600px;">
                Рестораны, магазины, услуги и развлечения. Честные отзывы и актуальная информация.
            </p>
            
            <div class="w-100" style="max-width: 700px;">
                <!-- Исправлено: action="/search" -->
                <form action="/search" method="GET" class="position-relative">
                    <input type="text" name="q" 
                           class="form-control form-control-lg border-0 shadow-lg rounded-pill py-3 ps-4 pe-5" 
                           placeholder="Что вы ищете? Например: плов, отель, аптека..." required>
                    <button class="btn btn-primary rounded-circle position-absolute top-50 end-0 translate-middle-y me-2" 
                            type="submit" style="width: 46px; height: 46px;">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="mt-4 d-flex flex-wrap justify-content-center gap-2">
                <!-- Исправлены ссылки на ЧПУ -->
                <a href="/search?q=кофе" class="btn btn-sm btn-white bg-white bg-opacity-10 hover-white text-white border-0 rounded-pill backdrop-blur px-3">☕ Кофе</a>
                <a href="/category/food" class="btn btn-sm btn-white bg-white bg-opacity-10 hover-white text-white border-0 rounded-pill backdrop-blur px-3">🍔 Еда</a>
                <a href="/category/hotels" class="btn btn-sm btn-white bg-white bg-opacity-10 hover-white text-white border-0 rounded-pill backdrop-blur px-3">🏨 Отели</a>
            </div>
        </div>
    </div>
</div>