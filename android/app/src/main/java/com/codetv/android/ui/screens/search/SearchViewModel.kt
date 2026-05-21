package com.codetv.android.ui.screens.search

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codetv.android.data.api.CodetvRepository
import com.codetv.android.data.model.Channel
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SearchUiState(
    val query: String = "",
    val results: List<Channel> = emptyList(),
    val isLoading: Boolean = false,
)

@HiltViewModel
class SearchViewModel @Inject constructor(
    private val repository: CodetvRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(SearchUiState())
    val uiState: StateFlow<SearchUiState> = _uiState

    private var searchJob: Job? = null

    fun search(query: String) {
        _uiState.value = _uiState.value.copy(query = query)
        searchJob?.cancel()
        if (query.length < 2) {
            _uiState.value = _uiState.value.copy(results = emptyList(), isLoading = false)
            return
        }
        searchJob = viewModelScope.launch {
            delay(300) // debounce
            _uiState.value = _uiState.value.copy(isLoading = true)
            try {
                val result = repository.search(query)
                _uiState.value = _uiState.value.copy(
                    results = result.getOrNull()?.channels ?: emptyList(),
                    isLoading = false,
                )
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(isLoading = false)
            }
        }
    }

    fun clear() {
        _uiState.value = SearchUiState()
    }
}
