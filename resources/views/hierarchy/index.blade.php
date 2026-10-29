@extends('layouts.app')

@section('title', 'Interactive Hierarchy Visualization — GmatGroup')

@section('content')
<div class="hierarchy-wrapper">

    <!-- Top Floating Toolbar -->
    <div class="floating-toolbar">
        <!-- Live Search -->
        <div class="input-group input-group-sm" style="width: 240px;">
            <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="member-search" class="form-control border-start-0 ps-0" placeholder="Search member (e.g. Alice, MEM-001)...">
            <button class="btn btn-outline-secondary border-start-0 d-none" type="button" id="clear-search">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <div class="vr my-1"></div>

        <!-- Sale Transaction Selector -->
        <div class="d-flex align-items-center gap-2">
            <label for="sale-select" class="small fw-semibold text-muted text-nowrap d-none d-md-inline">
                <i class="bi bi-receipt-cutoff me-1 text-primary"></i> Inspect Sale:
            </label>
            <select id="sale-select" class="form-select form-select-sm" style="width: 250px;">
                <option value="">-- Select a Sale to Highlight Path --</option>
                @foreach ($sales as $s)
                    <option value="{{ $s['id'] }}">
                        {{ $s['transaction_code'] }} — ${{ number_format($s['sale_amount'], 2) }} ({{ $s['seller_name'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <button id="btn-clear-path" class="btn btn-sm btn-outline-secondary text-nowrap d-none" title="Clear highlighted path">
            <i class="bi bi-x-circle me-1"></i> Clear Path
        </button>

        <div class="vr my-1 d-none d-lg-block"></div>

        <!-- Controls -->
        <div class="d-none d-lg-flex align-items-center gap-1">
            <button id="btn-fit-screen" class="btn btn-sm btn-light border text-nowrap" title="Fit Entire Hierarchy to Screen">
                <i class="bi bi-aspect-ratio me-1"></i> Fit View
            </button>
            <button id="btn-toggle-all" class="btn btn-sm btn-light border text-nowrap" title="Expand/Collapse All Branches">
                <i class="bi bi-arrows-collapse me-1"></i> Collapse All
            </button>
        </div>
    </div>

    <!-- Canvas Pan & Zoom Viewport -->
    <div id="canvas-container" class="canvas-container">
        <!-- SVG Connecting Edges Layer -->
        <svg id="hierarchy-svg">
            <defs>
                <linearGradient id="pathGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#10b981" />
                    <stop offset="100%" stop-color="#059669" />
                </linearGradient>
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>
            <g id="svg-edges-group"></g>
        </svg>

        <!-- HTML Cards Layer (Transformed with Canvas) -->
        <div id="nodes-layer"></div>

        <!-- Loading State Overlay -->
        <div id="loading-overlay" class="canvas-overlay">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="fw-bold text-dark">Building Interactive Hierarchy...</h5>
            <p class="text-muted small">Loading network members, relationship edges, and commission models.</p>
        </div>

        <!-- Error State Overlay -->
        <div id="error-overlay" class="canvas-overlay d-none">
            <div class="rounded-circle bg-danger-subtle text-danger p-3 mb-3">
                <i class="bi bi-exclamation-triangle fs-2"></i>
            </div>
            <h5 class="fw-bold text-danger">Failed to Load Hierarchy</h5>
            <p id="error-message" class="text-muted small text-center max-w-sm mb-3">An error occurred while fetching network data from the server.</p>
            <button id="btn-retry" class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-clockwise me-1"></i> Try Again
            </button>
        </div>
    </div>

    <!-- Zoom & Navigation Buttons -->
    <div class="zoom-controls">
        <button id="zoom-in" class="zoom-btn" title="Zoom In">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button id="zoom-reset" class="zoom-btn" title="Reset Zoom (100%)">
            <i class="bi bi-arrow-counterclockwise"></i>
        </button>
        <button id="zoom-out" class="zoom-btn" title="Zoom Out">
            <i class="bi bi-dash-lg"></i>
        </button>
        <button id="zoom-fit" class="zoom-btn" title="Fit to Screen">
            <i class="bi bi-bounding-box"></i>
        </button>
    </div>

    <!-- Calculation & Member Detail Drawer -->
    <aside id="detail-panel" class="detail-panel">
        <div class="detail-panel-header">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded-2 bg-primary-subtle text-primary">
                    <i id="detail-icon" class="bi bi-info-circle-fill"></i>
                </div>
                <div>
                    <h6 id="detail-title" class="fw-bold mb-0 text-dark">Inspector Panel</h6>
                    <small id="detail-subtitle" class="text-muted">Click a member or sale to inspect</small>
                </div>
            </div>
            <button id="btn-close-panel" type="button" class="btn-close" aria-label="Close"></button>
        </div>

        <div class="detail-panel-body" id="detail-content">
            <!-- Default Placeholder State -->
            <div class="text-center py-5 text-muted">
                <i class="bi bi-hand-index-thumb fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                <p class="small mb-1 fw-semibold">No Item Selected</p>
                <p class="small text-muted">Click any member card to view their profile, or select a sale from the top toolbar to trace its upward commission path.</p>
            </div>
        </div>
    </aside>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // State Variables
    let graphData = { nodes: [], edges: [], sales: [], max_generations: 5 };
    let collapsedNodes = new Set();
    let currentScale = 1.0;
    let translateX = 100;
    let translateY = 80;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let activeSalePath = null;
    let selectedMemberId = null;

    // Card dimensions
    const CARD_WIDTH = 250;
    const CARD_HEIGHT = 145;
    const HORIZONTAL_GAP = 60;
    const VERTICAL_GAP = 90;

    // Canvas & Container Elements
    const $container = $('#canvas-container');
    const $nodesLayer = $('#nodes-layer');
    const $svgEdgesGroup = $('#svg-edges-group');
    const $detailPanel = $('#detail-panel');
    const $detailContent = $('#detail-content');

    // 1. Initial Data Fetch
    function loadHierarchyData() {
        $('#loading-overlay').removeClass('d-none');
        $('#error-overlay').addClass('d-none');

        $.ajax({
            url: "{{ route('api.hierarchy.data') }}",
            type: "GET",
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    graphData = response;
                    $('#loading-overlay').addClass('d-none');
                    renderHierarchy();
                    fitToScreen();
                } else {
                    showError(response.message || 'Server returned unsuccessful response.');
                }
            },
            error: function (xhr, status, error) {
                showError('Error connecting to hierarchy API: ' + (xhr.responseJSON?.message || error));
            }
        });
    }

    function showError(msg) {
        $('#loading-overlay').addClass('d-none');
        $('#error-overlay').removeClass('d-none');
        $('#error-message').text(msg);
    }

    $('#btn-retry').on('click', loadHierarchyData);

    // 2. Tree Layout Algorithm (Generates (x, y) coordinates for rectangular cards)
    function computeTreeLayout() {
        const root = graphData.nodes.find(n => n.parent_id === null);
        if (!root) return {};

        const positions = {};
        let nextX = 50;

        function getVisibleChildren(nodeId) {
            if (collapsedNodes.has(nodeId)) return [];
            return graphData.nodes.filter(n => n.parent_id === nodeId);
        }

        // Post-order traversal to calculate X and Y
        function layoutNode(nodeId, depth) {
            const children = getVisibleChildren(nodeId);
            const y = 50 + depth * (CARD_HEIGHT + VERTICAL_GAP);

            if (children.length === 0) {
                const x = nextX;
                nextX += CARD_WIDTH + HORIZONTAL_GAP;
                positions[nodeId] = { x, y, depth };
                return x;
            }

            const childXCoords = [];
            for (const child of children) {
                childXCoords.push(layoutNode(child.id, depth + 1));
            }

            // Center parent above its visible children
            const minChildX = Math.min(...childXCoords);
            const maxChildX = Math.max(...childXCoords);
            const x = (minChildX + maxChildX) / 2;

            positions[nodeId] = { x, y, depth };
            return x;
        }

        layoutNode(root.id, 0);
        return positions;
    }

    // 3. Render Hierarchy (Nodes and Connecting SVG Lines)
    function renderHierarchy() {
        const positions = computeTreeLayout();

        // Clear existing canvas
        $nodesLayer.empty();
        $svgEdgesGroup.empty();

        const renderedNodeIds = new Set(Object.keys(positions).map(Number));

        // Render Connecting SVG Edges
        graphData.edges.forEach(edge => {
            if (renderedNodeIds.has(edge.source) && renderedNodeIds.has(edge.target)) {
                const parentPos = positions[edge.source];
                const childPos = positions[edge.target];

                // Start: Bottom center of parent card
                const startX = parentPos.x + CARD_WIDTH / 2;
                const startY = parentPos.y + CARD_HEIGHT;

                // End: Top center of child card
                const endX = childPos.x + CARD_WIDTH / 2;
                const endY = childPos.y;

                // Path highlight check
                const isEdgeInPath = activeSalePath && activeSalePath.path_edges.includes(edge.id);
                let edgeClass = 'hierarchy-edge';
                let isEdgeEligible = true;
                if (isEdgeInPath) {
                    const uplineGen = activeSalePath.generation_breakdown.find(g => g.member_id === edge.source);
                    if (uplineGen && !uplineGen.is_eligible) {
                        isEdgeEligible = false;
                        edgeClass = 'hierarchy-edge edge-ineligible';
                    } else {
                        edgeClass = 'hierarchy-edge edge-in-path';
                    }
                }

                // Orthogonal bezier curve
                const midY = (startY + endY) / 2;
                const pathD = `M ${startX} ${startY} C ${startX} ${midY}, ${endX} ${midY}, ${endX} ${endY}`;

                // Append SVG Path
                const pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                pathEl.setAttribute('d', pathD);
                pathEl.setAttribute('class', edgeClass);
                pathEl.setAttribute('id', `svg-${edge.id}`);
                $svgEdgesGroup.append(pathEl);

                // Append Rate Badge at midpoint
                const labelX = (startX + endX) / 2;
                const labelY = midY;
                const rateText = `${parseFloat(edge.rate).toFixed(2)}%`;

                let labelBgClass = 'edge-label-bg';
                let labelTextClass = 'edge-label-text';
                if (isEdgeInPath) {
                    if (isEdgeEligible) {
                        labelBgClass += ' active-edge';
                        labelTextClass += ' active-edge';
                    } else {
                        labelBgClass += ' cutoff-edge';
                        labelTextClass += ' cutoff-edge';
                    }
                }

                const labelBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                labelBg.setAttribute('x', labelX - 24);
                labelBg.setAttribute('y', labelY - 10);
                labelBg.setAttribute('width', 48);
                labelBg.setAttribute('height', 20);
                labelBg.setAttribute('class', labelBgClass);
                $svgEdgesGroup.append(labelBg);

                const labelTxt = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                labelTxt.setAttribute('x', labelX);
                labelTxt.setAttribute('y', labelY);
                labelTxt.setAttribute('class', labelTextClass);
                labelTxt.textContent = rateText;
                $svgEdgesGroup.append(labelTxt);
            }
        });

        // Render HTML Member Cards
        graphData.nodes.forEach(member => {
            if (!positions[member.id]) return;

            const pos = positions[member.id];
            const isSelected = selectedMemberId === member.id;
            const hasChildren = member.has_children;
            const isCollapsed = collapsedNodes.has(member.id);

            // Path highlight status
            let pathClass = '';
            if (activeSalePath) {
                if (member.id === activeSalePath.seller?.id) {
                    pathClass = 'is-seller';
                } else if (activeSalePath.path_nodes.includes(member.id)) {
                    // Check if eligible generation
                    const genInfo = activeSalePath.generation_breakdown.find(g => g.member_id === member.id);
                    if (genInfo) {
                        pathClass = genInfo.is_eligible ? 'in-path-eligible' : 'in-path-ineligible';
                    }
                }
            }

            const card = $(`
                <div class="member-card ${isSelected ? 'active-selected' : ''} ${pathClass}" 
                     id="node-${member.id}" 
                     data-id="${member.id}"
                     style="left: ${pos.x}px; top: ${pos.y}px;">
                    <div class="member-card-header">
                        <span class="member-code">${member.member_code}</span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge ${member.status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'} py-1 px-1.5" style="font-size: 0.65rem;">
                                ${member.status.toUpperCase()}
                            </span>
                            <span class="level-badge level-${member.level}">L${member.level}</span>
                        </div>
                    </div>
                    <div class="member-card-body">
                        <div class="member-name" title="${member.name}">${member.name}</div>
                        <div class="member-title" title="${member.title}">${member.title}</div>
                        
                        <div class="member-meta-row">
                            <span class="text-muted">Override:</span>
                            <span class="fw-bold text-indigo-700">${parseFloat(member.override_rate).toFixed(2)}%</span>
                        </div>
                        
                        <div class="member-meta-row">
                            <span class="text-muted">Earned:</span>
                            <span class="fw-bold text-success">$${parseFloat(member.commission_earned).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                    </div>
                    ${hasChildren ? `
                        <div class="collapse-toggle-btn" data-toggle-id="${member.id}" title="${isCollapsed ? 'Expand Children' : 'Collapse Children'}">
                            <i class="bi ${isCollapsed ? 'bi-plus-lg' : 'bi-dash-lg'}"></i>
                        </div>
                    ` : ''}
                </div>
            `);

            // Node click handler
            card.on('click', function (e) {
                if ($(e.target).closest('.collapse-toggle-btn').length > 0) return;
                selectMember(member.id);
            });

            // Toggle collapse/expand handler
            card.find('.collapse-toggle-btn').on('click', function (e) {
                e.stopPropagation();
                toggleCollapse(member.id);
            });

            $nodesLayer.append(card);
        });

        applyTransform();
    }

    // 4. Zoom, Pan & Fit-to-Screen Logic
    function applyTransform() {
        const transformStr = `translate(${translateX}px, ${translateY}px) scale(${currentScale})`;
        $nodesLayer.css('transform', transformStr);
        $svgEdgesGroup.attr('transform', transformStr);
    }

    function fitToScreen() {
        const positions = computeTreeLayout();
        const nodeIds = Object.keys(positions);
        if (nodeIds.length === 0) return;

        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        nodeIds.forEach(id => {
            const p = positions[id];
            minX = Math.min(minX, p.x);
            maxX = Math.max(maxX, p.x + CARD_WIDTH);
            minY = Math.min(minY, p.y);
            maxY = Math.max(maxY, p.y + CARD_HEIGHT);
        });

        const graphW = maxX - minX;
        const graphH = maxY - minY;
        const containerW = $container.width();
        const containerH = $container.height();

        const padding = 80;
        const scaleX = (containerW - padding * 2) / graphW;
        const scaleY = (containerH - padding * 2) / graphH;
        currentScale = Math.min(Math.max(Math.min(scaleX, scaleY), 0.35), 1.2);

        translateX = (containerW - graphW * currentScale) / 2 - minX * currentScale;
        translateY = (containerH - graphH * currentScale) / 2 - minY * currentScale;

        applyTransform();
    }

    // Mouse Panning
    $container.on('mousedown', function (e) {
        if ($(e.target).closest('.member-card, .floating-toolbar, .zoom-controls, .detail-panel').length > 0) return;
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        $container.addClass('panning');
    });

    $(window).on('mousemove', function (e) {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        applyTransform();
    });

    $(window).on('mouseup', function () {
        if (isDragging) {
            isDragging = false;
            $container.removeClass('panning');
        }
    });

    // Mouse Wheel Zoom
    $container.on('wheel', function (e) {
        e.preventDefault();
        const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
        zoomAtPoint(delta, e.clientX, e.clientY);
    });

    function zoomAtPoint(delta, mouseX, mouseY) {
        const rect = $container[0].getBoundingClientRect();
        const cx = mouseX - rect.left;
        const cy = mouseY - rect.top;

        const newScale = Math.min(Math.max(currentScale + delta, 0.25), 2.0);
        if (newScale === currentScale) return;

        translateX = cx - (cx - translateX) * (newScale / currentScale);
        translateY = cy - (cy - translateY) * (newScale / currentScale);
        currentScale = newScale;

        applyTransform();
    }

    // Zoom Buttons
    $('#zoom-in').on('click', () => zoomAtPoint(0.15, $container.width() / 2, $container.height() / 2));
    $('#zoom-out').on('click', () => zoomAtPoint(-0.15, $container.width() / 2, $container.height() / 2));
    $('#zoom-reset').on('click', function () {
        currentScale = 1.0;
        fitToScreen();
    });
    $('#zoom-fit, #btn-fit-screen').on('click', fitToScreen);

    // 5. Branch Collapse & Expand
    function toggleCollapse(nodeId) {
        if (collapsedNodes.has(nodeId)) {
            collapsedNodes.delete(nodeId);
        } else {
            collapsedNodes.add(nodeId);
        }
        renderHierarchy();
    }

    let allCollapsed = false;
    $('#btn-toggle-all').on('click', function () {
        if (!allCollapsed) {
            graphData.nodes.forEach(n => {
                if (n.has_children) collapsedNodes.add(n.id);
            });
            $(this).html('<i class="bi bi-arrows-expand me-1"></i> Expand All');
            allCollapsed = true;
        } else {
            collapsedNodes.clear();
            $(this).html('<i class="bi bi-arrows-collapse me-1"></i> Collapse All');
            allCollapsed = false;
        }
        renderHierarchy();
        fitToScreen();
    });

    // 6. Member Selection & Detail View
    function selectMember(memberId) {
        selectedMemberId = memberId;
        $('.member-card').removeClass('active-selected');
        $(`#node-${memberId}`).addClass('active-selected');

        const member = graphData.nodes.find(n => n.id === memberId);
        if (!member) return;

        const parent = member.parent_id ? graphData.nodes.find(n => n.id === member.parent_id) : null;
        const children = graphData.nodes.filter(n => n.parent_id === member.id);

        $('#detail-icon').attr('class', 'bi bi-person-badge-fill text-primary');
        $('#detail-title').text(member.name);
        $('#detail-subtitle').text(`${member.member_code} • ${member.title}`);

        $detailContent.html(`
            <div class="mb-4">
                <span class="badge ${member.status === 'active' ? 'bg-success' : 'bg-secondary'} mb-2">
                    ${member.status.toUpperCase()}
                </span>
                <span class="level-badge level-${member.level}">Hierarchy Level ${member.level}</span>
                
                <div class="list-group list-group-flush mt-3 border-top border-bottom">
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Direct Upline:</span>
                        <span class="fw-semibold small">${parent ? parent.name + ' (' + parent.member_code + ')' : '<span class="badge bg-light text-dark">Root Leader</span>'}</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Override Rate:</span>
                        <span class="fw-bold small text-primary">${parseFloat(member.override_rate).toFixed(2)}%</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Personal Sales:</span>
                        <span class="fw-semibold small">$${parseFloat(member.personal_sales).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Commissions Earned:</span>
                        <span class="fw-bold small text-success">$${parseFloat(member.commission_earned).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Email:</span>
                        <span class="small">${member.email}</span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted small">Phone:</span>
                        <span class="small">${member.phone}</span>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold small text-dark mb-2">Direct Team Members (${children.length})</h6>
            ${children.length > 0 ? `
                <div class="list-group list-group-flush border-top">
                    ${children.map(c => `
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">${c.name}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">${c.member_code} • ${c.title}</div>
                            </div>
                            <span class="badge bg-light text-dark border">${parseFloat(c.override_rate).toFixed(2)}%</span>
                        </div>
                    `).join('')}
                </div>
            ` : '<p class="small text-muted fst-italic">No direct team downlines.</p>'}
        `);

        $detailPanel.addClass('open');
    }

    $('#btn-close-panel').on('click', function () {
        $detailPanel.removeClass('open');
    });

    // 7. Inspect Sale Path & Calculation Details
    $('#sale-select').on('change', function () {
        const saleId = $(this).val();
        if (!saleId) {
            clearSalePath();
            return;
        }

        $.ajax({
            url: "{{ route('api.hierarchy.calculate-path') }}",
            type: "POST",
            data: {
                _token: csrfToken,
                sale_id: saleId,
            },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    activeSalePath = response;
                    $('#btn-clear-path').removeClass('d-none');
                    renderHierarchy();
                    displayCalculationDetail(response);
                }
            },
            error: function (xhr) {
                alert('Error computing sale path: ' + (xhr.responseJSON?.message || 'Server error'));
            }
        });
    });

    function clearSalePath() {
        activeSalePath = null;
        $('#sale-select').val('');
        $('#btn-clear-path').addClass('d-none');
        renderHierarchy();
        if (selectedMemberId) {
            selectMember(selectedMemberId);
        } else {
            $detailPanel.removeClass('open');
        }
    }

    $('#btn-clear-path').on('click', clearSalePath);

    function displayCalculationDetail(calc) {
        $('#detail-icon').attr('class', 'bi bi-calculator-fill text-success');
        $('#detail-title').text(`Commission Path Audit`);
        $('#detail-subtitle').text(calc.sale.transaction_code);

        const seller = calc.seller;
        const breakdown = calc.generation_breakdown;

        $detailContent.html(`
            <!-- Sale Header Summary Card -->
            <div class="card border-0 bg-primary-subtle text-primary p-3 mb-3 rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold text-muted">Original Sale Amount:</span>
                    <span class="fs-5 fw-bold text-dark">$${parseFloat(calc.sale.sale_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Originating Seller:</span>
                    <span class="small fw-bold">${seller.name} (${seller.member_code})</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="small text-muted">Max Generations:</span>
                    <span class="badge bg-primary">${calc.max_generations} Tiers</span>
                </div>
            </div>

            <!-- Total Payout Banner -->
            <div class="alert alert-success d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                <div>
                    <div class="small fw-bold">Total Overrides Payable:</div>
                    <small class="text-muted">${calc.eligible_upline_count} Eligible Beneficiaries</small>
                </div>
                <span class="fs-5 fw-extrabold text-success">$${parseFloat(calc.total_commission_payable).toFixed(2)}</span>
            </div>

            <!-- Generation Breakdown Table -->
            <h6 class="fw-bold small text-dark mb-2">Generation-by-Generation Payouts</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm gen-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Gen</th>
                            <th>Beneficiary</th>
                            <th>Rate</th>
                            <th class="text-end">Payout</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${breakdown.map(g => `
                            <tr class="${g.is_eligible ? 'table-success-subtle' : 'table-danger-subtle opacity-75'}">
                                <td class="fw-bold text-center">G${g.generation}</td>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 100px;">${g.member_name}</div>
                                    <small class="text-muted">${g.member_code}</small>
                                </td>
                                <td class="fw-bold text-indigo-700">${parseFloat(g.rate_applied).toFixed(2)}%</td>
                                <td class="text-end fw-bold ${g.is_eligible ? 'text-success' : 'text-danger'}">
                                    $${parseFloat(g.commission_amount).toFixed(2)}
                                </td>
                                <td>
                                    <span class="badge ${g.is_eligible ? 'bg-success' : 'bg-danger'} py-1" style="font-size: 0.65rem;">
                                        ${g.is_eligible ? 'PAID' : 'CUTOFF'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            <div class="alert alert-light border mt-3 p-2 small text-muted">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                <strong>Audit Note:</strong> Each generation override uses the full original sale base ($${parseFloat(calc.sale.sale_amount).toFixed(2)}). Generations exceeding the ceiling of ${calc.max_generations} tiers are strictly excluded from payouts.
            </div>
        `);

        $detailPanel.addClass('open');
    }

    // 8. Live Search
    $('#member-search').on('input', function () {
        const query = $(this).val().trim().toLowerCase();
        if (query.length > 0) {
            $('#clear-search').removeClass('d-none');
            const matched = graphData.nodes.find(n => 
                n.name.toLowerCase().includes(query) || 
                n.member_code.toLowerCase().includes(query)
            );

            if (matched) {
                // Ensure ancestors are expanded if collapsed
                let curr = matched.parent_id;
                while (curr !== null) {
                    collapsedNodes.delete(curr);
                    const pObj = graphData.nodes.find(n => n.id === curr);
                    curr = pObj ? pObj.parent_id : null;
                }
                renderHierarchy();
                selectMember(matched.id);

                // Center view on matched node
                const positions = computeTreeLayout();
                if (positions[matched.id]) {
                    const pos = positions[matched.id];
                    translateX = $container.width() / 2 - (pos.x + CARD_WIDTH / 2) * currentScale;
                    translateY = $container.height() / 2 - (pos.y + CARD_HEIGHT / 2) * currentScale;
                    applyTransform();
                }
            }
        } else {
            $('#clear-search').addClass('d-none');
        }
    });

    $('#clear-search').on('click', function () {
        $('#member-search').val('').trigger('input');
    });

    // Initial Load
    loadHierarchyData();
});
</script>
@endpush
