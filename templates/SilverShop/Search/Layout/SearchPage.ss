<div class="silvershop-search">
    <header class="silvershop-search__header">
        <h1>$Title</h1>
        $SearchForm
    </header>

    <div class="silvershop-search__body">
        <% if $Facets && $Facets.Count %>
            <aside class="silvershop-search__facets">
                <% loop $Facets %>
                    <% if $Options.Count %>
                        <div class="facet facet--$Name">
                            <h2 class="facet__title">$Title</h2>
                            <ul class="facet__options">
                                <% loop $Options %>
                                    <li class="facet__option<% if $Active %> is-active<% end_if %>">
                                        <a href="$Link" rel="nofollow">$Label <span class="facet__count">($Count)</span></a>
                                    </li>
                                <% end_loop %>
                            </ul>
                        </div>
                    <% end_if %>
                <% end_loop %>
            </aside>
        <% end_if %>

        <section class="silvershop-search__results">
            <p class="silvershop-search__summary">
                <% if $Query %>
                    <%t SilverShop\Search\SearchPage.RESULTS_FOR 'Results for "{query}"' query=$Query %>
                <% end_if %>
                <%t SilverShop\Search\SearchPage.TOTAL '{count} product(s)' count=$Total %>
            </p>

            <% if $Results %>
                <ul class="product-list">
                    <% loop $Results %>
                        <li class="product-list__item">
                            <a href="$Link" class="product-list__link">
                                <span class="product-list__title">$Title</span>
                                <span class="product-list__price">$Price.Nice</span>
                            </a>
                        </li>
                    <% end_loop %>
                </ul>

                <% if $Results.MoreThanOnePage %>
                    <nav class="pagination" aria-label="Pagination">
                        <% if $Results.NotFirstPage %><a class="pagination__prev" href="$Results.PrevLink" rel="nofollow">&laquo;</a><% end_if %>
                        <% loop $Results.Pages %>
                            <% if $CurrentBool %>
                                <span class="pagination__page is-current">$PageNum</span>
                            <% else %>
                                <% if $Link %><a class="pagination__page" href="$Link" rel="nofollow">$PageNum</a><% else %><span class="pagination__ellipsis">&hellip;</span><% end_if %>
                            <% end_if %>
                        <% end_loop %>
                        <% if $Results.NotLastPage %><a class="pagination__next" href="$Results.NextLink" rel="nofollow">&raquo;</a><% end_if %>
                    </nav>
                <% end_if %>
            <% else %>
                <p class="silvershop-search__empty"><%t SilverShop\Search\SearchPage.EMPTY 'No products matched your search.' %></p>
            <% end_if %>
        </section>
    </div>
</div>
